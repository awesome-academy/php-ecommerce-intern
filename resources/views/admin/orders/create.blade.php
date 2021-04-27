@extends('admin.app')

@section('admin-content')
<div class="row">
    <div class="col-sm-12 admin-content">
        <h1>{{ __('add_new_order') }}</h1>
    <div>
    <!--Display errors-->
    @include('messages.error')

    <!--Edit order form-->
    <form method="post" action="{{ route('orders.store') }}">
        @csrf
        <div class="form-group">
            <label for="user_name">{{ __('user_name') }}:</label>
            <select class="group_select" name="user_id">
                @foreach ($users as $user)
                    <option value="{{ $userId->id }}"> {{ $userId->name }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="name">{{ __('status') }}:</label>
            <select class="group_select" name="status">                
                    <option value="0"> {{ __('not_shipped_yet') }} </option>
                    <option value="1"> {{ __('shipped') }} </option>                
            </select>
        </div>

        <div class="form-group">
            <label for="price">{{ __('ordered_date') }}:</label>
            <input type="datetime-local" class="form-control" name="ordered_date" required/>
        </div>               

        <div class="form-group">
            <label for="address">{{ __('address') }}:</label>
            <input type="text" class="form-control" name="address" required/>
        </div>

        <div class="form-group">
            <label for="phone_number">{{ __('phone_number') }}:</label>
            <input type="number" class="form-control" name="phone_number" value="{{ $userId->phone_number }}"/>
        </div>

        <div class="form-group">
            <label for="description">{{ __('description') }}:</label>
            <input type="text" class="form-control" name="description"/>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('add_new_order') }}</button>  
        <button type="button"  class="btn_go_back btn btn-danger" >{{ __('go_back') }}</button>   
    </form>
</div>
@endsection
