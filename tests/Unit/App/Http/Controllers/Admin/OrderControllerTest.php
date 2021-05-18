<?php

namespace Tests\Unit\App\Http\Controllers\Admin;

use Tests\TestCase;
use Mockery;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Http\Controllers\Admin\OrderController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderControllerTest extends TestCase
{
    protected $mockOrderRepository, $controller;

    public function setUp() : void
    {
        parent::setUp();
        $this->mockOrderRepository = Mockery::mock(OrderRepositoryInterface::class)->makePartial();
        $this->controller = new OrderController($this->mockOrderRepository);
    }

    public function tearDown() : void
    {
        Mockery::close();
        unset($this->controller);
        parent::tearDown();
    }

    public function test_index_return_view()
    {
        $view = $this->controller->index();
        $this->assertEquals('admin.orders.index', $view->getName());
    }

    public function test_load_orders()
    {
        $orders = factory(Order::class, config('app.fake_records_number'))->raw();

        $this->mockOrderRepository
            ->shouldReceive('loadOrders')
            ->andReturn($orders);
        $result = $this->controller->loadOrders();

        $this->assertSame($orders, $result);
    }

    public function test_view_details()
    {
        $id = config('app.default_record_id');
        $orderDetails = factory(OrderDetail::class, config('app.fake_records_number'))->raw();
        // dd($orderDetails);

        $this->mockOrderRepository
            ->shouldReceive('getDetails')
            ->with($id)
            ->andReturn($orderDetails);

        $view = $this->controller->viewDetails($id);
        //Kiem tra ten view tra ve
        $this->assertEquals('admin.order-details.index', $view->getName());
        //Kiem tra trong Array tra ve co Key nay khong
        $this->assertArrayHasKey('orderDetails', $view->getData());
    }

    public function test_deny_order()
    {
        $request = new Request();
        $request->orderId = config('app.default_record_id');
        $order = factory(Order::class)->make();
        $this->mockOrderRepository
            ->shouldReceive('find')
            ->with($request->orderId, 'user')
            ->andReturn($order);

        $this->mockOrderRepository
            ->shouldReceive('update');
        $result = $this->controller->deny($request);
        $order = $order->toArray();

        $this->assertEquals($result, $order);
    }

    public function test_approve_order()
    {
        $request = new Request();
        $request->orderId = config('app.default_record_id');
        $order = factory(Order::class)->make([
            'user_id' => 7,
            'status' => 0,
            'ordered_date' => now(),
            'address' => 'Bac Ninh',
            'phone_number' => '0365749738',
        ]);

        $orderDetails = factory(OrderDetail::class, 2)->make();

        $this->mockOrderRepository
            ->shouldReceive('find')
            ->with($request->orderId, ['orderDetails.product', 'user'])
            ->andReturn($order);

        foreach ($order->orderDetails as $orderDetail) {
            $quantityLeft = $orderDetail->product->quantity_in_stock - $orderDetail->quantities;
            if ($quantityLeft >= 0) {
                $orderDetail->product->quantity_in_stock = $quantityLeft;
            } else {
                return abort(Response::HTTP_NOT_FOUND);
            }
        }
        $order->status = config('app.approved_order_status');
        $order = $order->toArray();
        $result = $this->controller->approve($request);

        $this->assertSame($result, $order);
    }
}
