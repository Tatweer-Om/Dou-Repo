<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index() {
        return view('modules.channel');
    }

public function getchannels() {
    return Channel::orderBy('id', 'DESC')->paginate(10);
}


 public function store(Request $request)
{
        $channel = new Channel();
        $channel->channel_name_en = $request->channel_name_en;
        $channel->channel_name_ar = $request->channel_name_ar;
        $channel->added_by = 'system';          
        $channel->user_id = 1;
    

    $channel->save();

    return response()->json($channel);
}

    public function update(Request $request, channel $channel) {

    $channel->channel_name_en = $request->channel_name_en;
    $channel->channel_name_ar = $request->channel_name_ar;
    $channel->updated_by = 'system_update';
    $channel->save();

    return response()->json($channel);
    }
    public function show(channel $channel) {
    return response()->json($channel);
}

    public function destroy(channel $channel) {
        $channel->delete();
        return response()->json(['message' => 'Deleted']);
    }


    
}
