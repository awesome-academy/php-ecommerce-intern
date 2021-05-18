<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Product;
use App\Repositories\Order\OrderRepositoryInterface;

class OrderController extends Controller
{
    protected $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->middleware('auth');
        $this->orderRepository = $orderRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.orders.index');
    }

    /**
     * View order details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function viewDetails($id)
    {
        $orderDetails = $this->orderRepository->getDetails($id);

        return view('admin.order-details.index', compact('orderDetails'));
    }

    /**
     * Load orders.
     *
     * @param
     * @return array
     */
    public function loadOrders() {
        return $this->orderRepository->loadOrders();
    }

    /**
     * Approve order.
     *
     * @param  Request $request
     * @return array $order
     */
    public function approve(Request $request) {
        $order = $this->orderRepository->find($request->orderId, ['orderDetails.product', 'user']);

        foreach ($order->orderDetails as $orderDetail) {
            $quantityLeft = $orderDetail->product->quantity_in_stock - $orderDetail->quantities;
            if ($quantityLeft >= 0) {
                $orderDetail->product->quantity_in_stock = $quantityLeft;
            } else {
                return abort(Response::HTTP_NOT_FOUND);
            }
        }
        $order->status = config('app.approved_order_status');
        $order->push();

        return $order->toArray();
    }

    /**
     * Deny order.
     *
     * @param  Request $request
     * @return array $order
     */
    public function deny(Request $request) {
        $order = $this->orderRepository->find($request->orderId, 'user');
        $this->orderRepository->update($request->orderId, [
            'status' => config('app.denied_order_status'),
        ]);

        return $order->toArray();
    }
}
