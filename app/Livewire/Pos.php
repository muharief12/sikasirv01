<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use App\Models\Product;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Livewire\Component;

class Pos extends Component implements HasForms
{

    use InteractsWithForms;
    public $search = '';
    public $name_customer = '';
    public $gender = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $note = '';
    public $payment_methods;
    public $payment_method_id = '';
    public $paid_amount = '';
    public $change_amount = '';
    public $order_items = [];
    public $total_price;

    public $listeners = [
        'scanResult' => 'handleScanResult',
    ];
    public function render()
    {
        $products = Product::where('stock', '>=', 1)->search($this->search)->paginate(10);
        return view('livewire.pos', compact('products'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Form Checkout')
                    ->schema([
                        TextInput::make('name_customer')
                            ->required()
                            ->maxLength(255)
                            ->default(fn() => $this->name_customer),
                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->default(fn() => $this->email),
                        TextInput::make('phone')
                            ->required()
                            ->maxLength(13)
                            ->default(fn() => $this->phone),
                        TextInput::make('address')
                            ->required()
                            ->maxLength(13)
                            ->default(fn() => $this->address),
                        Textarea::make('note')
                            ->required()
                            ->default(fn() => $this->note),
                        Select::make('gender')
                            ->options([
                                'Male' => 'Laki-laki',
                                'Female' => 'Perempuan',
                            ])
                            ->required(),
                        TextInput::make('total_price')
                            ->readOnly()
                            ->prefix('Rp '),
                        Select::make('payment_method_id')
                            ->required()
                            ->options($this->payment_methods->pluck('name', 'id')),
                        TextInput::make('paid_amount')
                            ->required()
                            ->prefix('Rp ')
                            ->live()
                            ->numeric()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $total = $this->calculateTotal();
                                $set('change_amount', $state - $total);
                            })
                            ->default(fn() => $this->paid_amount),
                        TextInput::make('change_amount')
                            ->required()
                            ->prefix('Rp ')
                            ->readOnly()
                            ->numeric()
                            ->default(fn() => $this->change_amount),

                    ])
            ]);
    }

    public function mount()
    {
        if (session()->has('orderItems')) {
            $this->order_items = session('orderItems');
        }

        $this->payment_methods = PaymentMethod::all();
        $this->form->fill(['payment_methods', $this->payment_methods]);
    }

    #Fungsi cart
    public function addToOrder($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            if ($product->stock <= 0) {
                Notification::make()
                    ->title('The stock of ' . $product->name . ' product is empty')
                    ->danger()
                    ->send();
                return;
            }

            $existingItemKey = null;
            foreach ($this->order_items as $key => $item) {
                if ($item['product_id'] == $productId) {
                    $existingItemKey = $key;
                    break;
                }
            }

            if ($existingItemKey !== null) {
                $this->order_items[$existingItemKey]['qty']++;
            } else {
                $this->order_items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'qty' => 1,
                ];
            }
            session()->put('orderItems', $this->order_items);
            Notification::make()
                ->title($product->name . ' added to cart')
                ->success()
                ->send();
        }
    }

    # Menampilkan order items
    public function viewOrderItems($orderItems)
    {
        $this->order_items = $orderItems;
        session()->put('orderItems', $orderItems);
    }

    # Menabah kuantitas item product di cart
    public function incQty($product_id)
    {
        $product = Product::find($product_id);

        if (!$product) {
            Notification::make()
                ->title('Sorry, the product is not found')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->order_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                if ($item['qty'] + 1 <= $product->stock) {
                    $this->order_items[$key]['qty']++;
                } else {
                    Notification::make()
                        ->title('Sorry, the product is until limited stock')
                        ->danger()
                        ->send();
                    return;
                }
                break;
            }
        }

        session()->put('orderItems', $this->order_items);
    }

    public function decQty($product_id)
    {
        foreach ($this->order_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                if ($this->order_items[$key]['qty'] > 1) {
                    $this->order_items[$key]['qty']--;
                } else {
                    unset($this->order_items[$key]);
                    $this->order_items = array_values($this->order_items);
                }
                break;
            }
        }

        session()->put('orderItems', $this->order_items);
    }

    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->order_items as $item) {
            $total += $item['qty'] * $item['price'];
        }
        $this->total_price = $total;
        return $total;
    }

    public function checkout()
    {
        $this->validate([
            'name_customer' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:13',
            'address' => 'required',
            'note' => 'required',
            'gender' => 'required|in:Male,Female',
            'payment_method_id' => 'required',
            'paid_amount' => 'required|integer',
            'change_amount' => 'required|integer',
        ]);
        $payment_method_id_temp = $this->payment_method_id;

        $order = Order::create([
            'name' => $this->name_customer,
            'email' => $this->email,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'address' => $this->phone,
            'total_price' => $this->calculateTotal(),
            'note' => $this->note,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'payment_method_id' => $payment_method_id_temp,
        ]);

        foreach ($this->order_items as $item) {
            OrderProduct::create([
                'product_id' => $item['product_id'],
                'order_id' => $order->id,
                'qty' => $item['qty'],
                'unit_price' => $item['price'],
            ]);
        }

        $this->order_items = [];
        session()->forget(['orderItems']);

        return redirect()->to('dashboard/orders');
    }

    public function handleScanResult($decodedText)
    {
        $product = Product::where('barcode', $decodedText)->first();

        if ($product) {
            $this->addToOrder($product->id);
        } else {
            Notification::make()
                ->title('Sorry, the product is not found!')
                ->danger()
                ->send();
        }
    }
}
