<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HandymanPayout;
use App\Models\HandymanType;
use App\Models\User;
use App\Models\BookingHandymanMapping;
use App\Models\ProviderPayout;
use App\Models\Booking;
use Yajra\DataTables\DataTables;
use App\Traits\NotificationTrait;
use App\Traits\EarningTrait;
use Carbon\Carbon;
use App\Models\CommissionEarning;
use App\Models\Wallet;
use Exception;

class HandymanPayoutController extends Controller
{
    use NotificationTrait;
    use EarningTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }


    public function index_data(DataTables $datatable,Request $request)
    {
        $handymanpayout = $request->handymanpayout;
        $query = HandymanPayout::query()->where('handyman_id',$handymanpayout);
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query->newquery();
        }

        return $datatable->eloquent($query)
        ->addColumn('check', function ($row) {
            return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
        })
        ->editColumn('payment_method', function($payout) {
            return !empty($payout->payment_method) ? ucfirst($payout->payment_method) : 'Cash';
        })
        ->editColumn('description', function($payout) {
            return !empty($payout->description) ? $payout->description : '-';
        })

        ->editColumn('handyman_id', function ($payout) {
            return view('handymanpayout.user', compact('payout'));
        })

        ->filterColumn('handyman_id',function($payout,$keyword){
            $payout->whereHas('handymans',function ($q) use($keyword){
                $q->where('first_name','like','%'.$keyword.'%');
            });
        })
        ->editColumn('amount', function($payout) {
            return ($payout->amount != null && isset($payout->amount)) ? getPriceFormat($payout->amount) : '-';
        })
        ->editColumn('created_at', function($payout) {
            return $payout->created_at;
        })
        ->addColumn('action', function($handymanpayout){
            return view('handymanpayout.action',compact('handymanpayout'))->render();
        })
        ->addIndexColumn()
        ->rawColumns(['check','title','action','status'])
            ->toJson();
    }

    /* bulck action method */
    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-status':
                $branches = HandymanPayout::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Provider Payout Status Updated';
                break;

            case 'delete':
                HandymanPayout::whereIn('id', $ids)->delete();
                $message = 'Bulk Provider Payout Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $auth_user = authSession();

        $payoutdata = new HandymanPayout;

        $pageTitle = trans('messages.add_button_form',['form' => trans('messages.handyman_payout')]);

        $handymandata = User::where('id',$id)->first();

        $commissionData = $handymandata->commission_earning()
                ->whereHas('getbooking', function ($query) {
                    $query->where('status', 'completed');
                })
                ->where('commission_status', 'unpaid')
                ->where('user_type', 'handyman');

        $total_earning = $commissionData->sum('commission_amount');

        // $handymantype_id = !empty($handymandata->handymantype_id) ? $handymandata->handymantype_id : 1;
        // $get_commission = HandymanType::withTrashed()->where('id',$handymantype_id)->first();

        // $totalEarning = HandymanPayout::where('handyman_id',$id)->sum('amount');

        // $commission = $get_commission->commission;

        // $handyman_bookings = BookingHandymanMapping::with('bookings')->where('handyman_id',$id)->whereHas('bookings',function ($q){
        //     $q->whereNotNull('payment_id');
        // })->get();

        // $all_booking_total = $handyman_bookings->map(function ($booking) {
        //     return optional($booking->bookings)->total_amount;
        // })->toArray();

        // $total = array_reduce($all_booking_total, function ($value1, $value2) {
        //     return $value1 + $value2;
        // }, 0);

        // $earning =   ($commission * count($handyman_bookings));
        // if($get_commission->type === 'percent'){
        //     $earning =  ($total) * $commission / 100;
        // }
        // $final_amount = $earning - $totalEarning;

        // $providerEarning = ProviderPayout::where('provider_id',$handymandata->provider_id)->sum('amount');

        // if($earning <= 0){
        //     if (request()->wantsJson()) {
        //         return response()->json(['messages' => __('messages.provider_earning_error'), 'status' => false]);
        //     } else {
        //         return redirect()->route('home')->with('error', __('messages.provider_earning_error'));
        //     }
        // }

        $payoutdata->amount = $total_earning;

        // $payoutdata->amount = number_format((float)$final_amount, 2, '.', '');

        $payoutdata->handyman_id = $id;

        return view('handymanpayout.create', compact('pageTitle' ,'payoutdata' ,'auth_user' ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $auth_user = authSession();
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->except('_token');

        if($data['amount']<=0){

            if ( request()->is('api*')){
                $message = __('messages.amount_value_error');
                return comman_message_response($message);
              }
      

            return  redirect()->back()->withErrors(trans('messages.amount_value_error'));
        }


        $handymandata = User::where('id',$data['handyman_id'])->first();

        $providerEarning = ProviderPayout::where('provider_id',$handymandata->provider_id)->sum('amount');
        $handymanEarning = HandymanPayout::where('handyman_id',$handymandata->id)->sum('amount');

        $providerEarning = $providerEarning-$handymanEarning;

      
        if($providerEarning < $data['amount']){


        if ( request()->is('api*')){
            $message = __('messages.less_provider_earning');
            return comman_message_response($message);
          }
  

            return redirect()->back()->with('error', __('messages.less_provider_earning'));
        }
        if ($data['payment_method'] === 'wallet') {
            $wallet = Wallet::where('user_id', $handymandata->provider_id)->first();

            if($wallet->amount < $data['amount']){


                if ( request()->is('api*')){
                    $message = __('messages.less_provider_earning');
                    return comman_message_response($message);
                  }
          
    
                }



            if ($wallet) {
                if ($wallet->amount >= $data['amount']) {
                    $wallet->amount -= $data['amount'];
                    $wallet->save();
                    $activity_data = [
                            'activity_type' => 'wallet_payout_transfer',
                            'transfer_amount' => $data['amount'] ,
                            'wallet' => $wallet,
                            'handyman_id' => $handymandata->id,
                        ];
                        $this->sendNotification($activity_data);
                } else {
                    throw new Exception('Insufficient funds in wallet.');
                }
            } else {
                throw new Exception('Wallet not found for the user.');
            }
        }


        $result = HandymanPayout::create($data);
        $providerPayoutUpdate = ProviderPayout::where('provider_id', $handymandata->provider_id)
            ->orderBy('id', 'desc') // Order by 'id' descending to get the latest record
            ->first(); // Retrieve the latest record
        $providerpayout = $providerPayoutUpdate->amount - $data['amount'];
        if ($providerPayoutUpdate) {
            // Update the 'amount' of the most recent provider payout
            $providerPayoutUpdate->update(['amount' => $providerpayout]);
        }

        CommissionEarning::where('employee_id', $handymandata->id)->where('commission_status','unpaid')->update(['commission_status' => 'paid']);
        $activity_data = [
            'type' => 'handyman_payout',
            'activity_type' => 'handyman_payout',
            'id' => $result->id,
            'user_id' => $result->handyman_id,
            'pay_date' => $result->paid_date,
            'amount' => $result->amount,
            'provider_id' => $handymandata->provider_id,
        ];
        $this->sendNotification($activity_data);
        if($result){

            if($data['payment_method'] === 'wallet'){

                $wallet = Wallet::where('user_id',$data['handyman_id'])->first();
                if($wallet){

                    $wallet_amount = $wallet->amount;
                    $payout_amount = $result->amount;
                    $final_wallet_amount = $wallet_amount + $payout_amount;
                    $wallet->amount =  $final_wallet_amount;
                    $wallet->save();
                    $activity_data = [
                        'activity_type' => 'wallet_payout_transfer',
                        'transfer_amount' => $payout_amount ,
                        'wallet' => $wallet,
                    ];
                    $this->sendNotification($activity_data);


                }else{
                    $wallet = array(
                        'title' => $handymandata->display_name,
                        'user_id' => $handymandata->id,
                        'amount' => $result->amount,
                    );
                    $result = Wallet::create($wallet);
                }
            }
        }

   

        if ( request()->is('api*')){
            $message = __('messages.created_success',['form' => 'Provider Payout']);
            return comman_message_response($message);
          }
  

        return redirect()->route('handymanpayout.show',$handymandata->id)->with('success', __('messages.created_success',['form' => 'Handyman Payout']));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $handymandata = User::where('user_type','handyman')->where('id',$id)->first();
        if (($handymandata->id || $handymandata->provider_id) != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            // Your code here
            return redirect(route('home'))->withErrors(trans('messages.demo_permission_denied'));
        }
        
        $pageTitle = trans('messages.payout_history' );
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('handymanpayout.view', compact('pageTitle','auth_user','assets','handymandata'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $handymanpayout = HandymanPayout::find($id);
        $msg= __('messages.msg_fail_to_delete',['item' => __('messages.handymanpayout')] );

        if($handymanpayout != '') {
            $handymanpayout->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.handymanpayout')] );
        }
        return comman_custom_response(['message'=> $msg, 'status' => true]);
    }

    public function handymanpayoutcreate($id)
    {
        $auth_user = authSession();

        $payoutdata = new HandymanPayout;

        $pageTitle = trans('messages.add_button_form',['form' => trans('messages.handyman_payout')]);

        $handymandata = User::where('id',$id)->first();

        $bookings=Booking::where('status', 'completed')
        ->whereHas('payment', function($query) {
            $query->where('payment_status', 'paid');
        })->with('handymanAdded')->whereHas('handymanAdded', function ($query) use ($id) {
            $query->where('handyman_id', $id);
        })->get();


        $handymanEarning=$this->getHandymanCommission($bookings);


        $total_payout_earning = HandymanPayout::where('handyman_id',$id)->sum('amount');

        $payout_due= $handymanEarning- $total_payout_earning;

        if($payout_due <= 0){
            if (request()->wantsJson()) {
                return response()->json(['messages' => __('messages.provider_earning_error'), 'status' => false]);
            } else {
                return redirect()->route('home')->with('error', __('messages.provider_earning_error'));
            }
        }


        $payoutdata->amount = number_format((float)$payout_due, 2, '.', '');

        $payoutdata->handyman_id = $id;

        return view('handymanpayout.handymanpayoutcreate', compact('pageTitle' ,'payoutdata' ,'auth_user' ));
    }
}
