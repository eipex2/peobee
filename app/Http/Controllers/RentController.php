<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exception;
use App\Notifications\RentBook;
use App\Rent;
use App\Listing;
use App\User;

use App\Http\Requests;

class RentController extends Controller
{


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $this->validate($request, [
                'start_date'    => 'required',
                'end_date' => 'required',
            ]);

            //make sure user can not rent his own book
            if($request->input('user_id') != $request->input('list_user_id')){
                $rent = new Rent;
                $rent->user_id = $request->input('user_id');
                $rent->list_id = $request->input('list_id');
                $rent->start_date = date ("Y-m-d H:i:s", strtotime($request->input('start_date')));
                $rent->end_date = date ("Y-m-d H:i:s", strtotime($request->input('end_date')));;
                $rent->status = 'pending';

                $user = User::findOrFail($request->input('user_id'));

                $rent->save();

                $user->notify(new RentBook($user), 'rent');
            }
        }catch(Exception $e){
            return response()->error($e->getMessage());
        }

        return response()->success(compact('rent'));
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try{
            $rent = Rent::find($request->input('rent_id'));
            $listing = Listing::find($rent->list_id);
            $user = User::find($rent->user_id); //get the recipient of the notification;

            //make sure only listing owner can approve rental
            if($listing->user_id == Auth::id()) {
                $rent->status = $request->input('status');
                $rent->save();

                $type = $request->input('status') == 'approved' ? 'approved' : 'cancelled';
                $user->notify(new RentBook($user,$type), 'rent');
            }else{
              return response()->error(Auth::id());
            }
        }catch(Exception $e){
            return response()->error($e->getMessage());
        }

        return response()->success(compact('rent'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}