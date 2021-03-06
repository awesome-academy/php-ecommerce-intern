<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OrderFormRequest;
use App\Http\Requests\CommentFormRequest;
use App\Http\Requests\CommentReplyFormRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Order;
use App\Models\Comment;
use App\Models\OrderDetail;
Use Alert;
use Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (Auth::user()->role->id == config('app.admin_id')) {
            return view('admin.home');
        }

        return redirect(route('home'));
    }

    /**
     * Show the user cart
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function viewCart()
    {
        $productQuantity = Product::pluck('quantity_in_stock', 'id');

        return view('client.cart.index', compact('productQuantity'));
    }

    /**
     * Add Product to Cart.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addProductToCart($id)
    {
        $product = Product::findOrFail($id);
        $cart = session()->get('cart');

        // if cart is empty then this the first product
        if (!$cart) {
            $cart = [
                $id => [
                    "name" => $product->name,
                    "quantity" => 1,
                    "price" => $product->price,
                    "featured_img" => $product->featured_img,
                ],
            ];
            session()->put('cart', $cart);
            toast(__('add_product_successfully'), 'success');

            return redirect()->back();
        }
        // if cart not empty then check if this product exist then increase quantity
        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
            session()->put('cart', $cart);
            toast(__('add_product_successfully'), 'success');

            return redirect()->back();
        }
        // if item not exist in cart then add to cart with quantity = 1
        $cart[$id] = [
            "name" => $product->name,
            "quantity" => 1,
            "price" => $product->price,
            "featured_img" => $product->featured_img,
        ];
        session()->put('cart', $cart);
        toast(__('add_product_successfully'), 'success');

        return redirect()->back();
    }

    /**
     * Update Cart.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateCart(Request $request)
    {
        if ($request->id && $request->quantity) {
            $cart = session()->get('cart');
            $cart[$request->id]['quantity'] = $request->quantity;
            session()->put('cart', $cart);
        }

        return abort(Response::HTTP_NOT_FOUND);
    }

    /**
     * Delete product from Cart.
     *
     * @return \Illuminate\Http\Response
     */
    public function removeFromCart($id)
    {
        if (isset($id)) {
            $cart = session()->get('cart');
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put('cart', $cart);
            }
            toast(__('product_removed_successfully'), 'success');

            return redirect()->route('cart');
        }

        return abort(Response::HTTP_NOT_FOUND);
    }

    /**
     * Client - View product detail.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewProductDetail($id)
    {
        $product = Product::findOrFail($id);
        $comments = $product->comments()->with(['user', 'replies.user'])->paginate(config('app.records_per_page'));
        $relatedProducts = Product::where('category_id', $product->category_id)->limit(config('app.related_product_records'))->get();

        return view('client.products.show', compact('product', 'relatedProducts', 'comments'));
    }

    /**
     * Buy product action
     */
    public function buyProducts()
    {
        $productIds = array_keys(session('cart'));
        $quantitysInStock = Product::whereIn('id', $productIds)->get()->pluck('quantity_in_stock', 'id')->toArray();

        if (session('cart') != NULL) {
            foreach (session('cart') as $id => $detail) {
                if ($quantitysInStock[$id] < $detail['quantity']) {
                toast(__('order_failed_quantity_exceed'), 'error');

                return redirect(route('cart'));
            }

            return view('client.cart.order');
        }
        toast(__('order_failed_because_your_cart_is_empty'), 'error');

        return redirect(route('cart'));
        }
    }

    /**
     * Order product
     */
    public function order(OrderFormRequest $request)
    {
        //begin transaction
        DB::beginTransaction();
        try {
            $orderRecord = [
                'user_id' => Auth::user()->id,
                'status' => config('app.default_order_status'),
                'ordered_date' => now(),
                'phone_number' =>  $request->phone_number,
                'description' => $request->txt_note,
                'address' => $request->txt_address,
            ];
            $order = Order::create($orderRecord);

            $orderDetails = [];
            foreach (session('cart') as $id => $details) {
            array_push($orderDetails, [
                'order_id' => $order->id,
                'product_id' => $id,
                'quantities' => $details['quantity'],
                'status' => config('app.default_order_status'),
                ]);
            }

            OrderDetail::insert($orderDetails);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            toast(__('order_failed'), 'error');

            return redirect(route(cart));
        }
        $request->session()->forget('cart');
        toast(__('you_have_ordered_successfully'), 'success');

        return redirect(route('cart'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeComment(CommentFormRequest $request)
    {
        try {
            $comment = Comment::create($request->all());
            $comment->load('user')->toArray();

            return $comment;
        } catch (Exception $e) {
           return abort(Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function loadComments(Request $request) {
        $product = Product::findOrFail($request->product_id);
        $comments = $product->comments()->with('user', 'replies.user')->get();
        $comments = $comments->toArray();

        return $comments;
    }

    public function updateCartQuantity(Request $request)
    {
        $cart = session()->get('cart');
        if ($request->product_id && $request->new_quantity) {
            $cart[$request->product_id]['quantity'] = $request->new_quantity;
            session()->put('cart', $cart);
        }

        return $cart[$request->product_id];
    }

    //
    public function buyNow($id) {
        $product = Product::findOrFail($id);
        $cart = session()->get('cart');

        // if cart is empty then this the first product
        if (!$cart) {
            $cart = [
                $id => [
                    "name" => $product->name,
                    "quantity" => 1,
                    "price" => $product->price,
                    "featured_img" => $product->featured_img,
                ],
            ];
            session()->put('cart', $cart);

            return redirect(route('cart'));
        }
        // if cart not empty then check if this product exist then increase quantity
        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
            session()->put('cart', $cart);

            return redirect(route('cart'));
        }
        // if item not exist in cart then add to cart with quantity = 1
        $cart[$id] = [
            "name" => $product->name,
            "quantity" => 1,
            "price" => $product->price,
            "featured_img" => $product->featured_img,
        ];
        session()->put('cart', $cart);

        return redirect(route('cart'));
    }

    //Add product to Cart with quantity
    public function addProductsToCart(Request $request) {
        $product = Product::findOrFail($request->product_id);
        $cart = session()->get('cart');

        // if cart is empty then this the first product
        if (!$cart) {
            $cart = [
                $request->product_id => [
                    "name" => $product->name,
                    "quantity" => $request->quantity,
                    "price" => $product->price,
                    "featured_img" => $product->featured_img,
                ],
            ];
            session()->put('cart', $cart);

            return;
        }
        // if cart not empty then check if this product exist then increase quantity
        if (isset($cart[$request->product_id])) {
            $cart[$request->product_id]['quantity'] += $request->quantity;
            session()->put('cart', $cart);

            return;
        }
        // if item not exist in cart then add to cart with quantity = 1
        $cart[$request->product_id] = [
            "name" => $product->name,
            "quantity" => $request->quantity,
            "price" => $product->price,
            "featured_img" => $product->featured_img,
        ];
        session()->put('cart', $cart);

        return;
    }
}
