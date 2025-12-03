<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ResaleController;
use App\Http\Controllers\TailorController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\BoutiqueController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\WharehouseController;
use App\Http\Controllers\SpecialOrderController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('color', [ColorController::class, 'index'])->name('color');
Route::post('add_color', [ColorController::class, 'add_color'])->name('add_color');
Route::get('show_color', [ColorController::class, 'show_color'])->name('show_color');
Route::post('edit_color', [ColorController::class, 'edit_color'])->name('edit_color');
Route::post('update_color', [ColorController::class, 'update_color'])->name('update_color');
Route::post('delete_color', [ColorController::class, 'delete_color'])->name('delete_color');


Route::get('size', [SizeController::class, 'index']);
Route::post('sizes', [SizeController::class, 'store']);
Route::put('sizes/{size}', [SizeController::class, 'update']);
Route::delete('sizes/{size}', [SizeController::class, 'destroy']);
Route::get('sizes/list', [SizeController::class, 'getSizes']);
Route::get('sizes/{size}', [SizeController::class, 'show']);

Route::get('boutique', [BoutiqueController::class, 'index'])->name('boutique');
Route::post('add_boutique', [BoutiqueController::class, 'add_boutique'])->name('add_boutique');
Route::get('show_boutique', [BoutiqueController::class, 'show_boutique'])->name('show_boutique');
Route::get('boutique_list', [BoutiqueController::class, 'boutique_list'])->name('boutique_list');
Route::get('boutiques/list', [BoutiqueController::class, 'getboutiques']);
Route::get('/boutiques/{id}', [BoutiqueController::class, 'show']);
Route::get('edit_boutique/{id}', [BoutiqueController::class, 'edit_boutique'])->name('edit_boutique');
Route::post('update_boutique', [BoutiqueController::class, 'update_boutique'])->name('update_boutique');
Route::delete('boutique/{id}', [BoutiqueController::class, 'destroy'])->name('boutique.destroy');
Route::get('boutique_profile', [BoutiqueController::class, 'boutique_profile'])->name('boutique_profile');

Route::get('tailor', [TailorController::class, 'index']);
Route::post('tailors', [TailorController::class, 'store']);
Route::put('tailors/{tailor}', [TailorController::class, 'update']);
Route::delete('tailors/{tailor}', [TailorController::class, 'destroy']);
Route::get('tailors/list', [TailorController::class, 'gettailors']);
Route::get('tailors/{tailor}', [TailorController::class, 'show']);


Route::get('user', [UserController::class, 'index'])->name('user');
Route::post('users', [UserController::class, 'store']);
Route::put('users/{user}', [UserController::class, 'update']);
Route::delete('users/{user}', [UserController::class, 'destroy']);
Route::get('users/list', [UserController::class, 'getusers']);
Route::get('users/{user}', [UserController::class, 'show']);
Route::get('login_page', [UserController::class, 'login_page'])->name('login_page');
Route::post('/login-user', [UserController::class, 'login_user'])->name('login_user');

Route::get('colors', [ColorController::class, 'index']);
Route::post('colors', [ColorController::class, 'store']);
Route::put('colors/{color}', [ColorController::class, 'update']);
Route::delete('colors/{color}', [ColorController::class, 'destroy']);
Route::get('colors/list', [ColorController::class, 'getcolors']);
Route::get('colors/{color}', [ColorController::class, 'show']);

Route::get('channels', [ChannelController::class, 'index'])->name('channel');
Route::post('channels', [ChannelController::class, 'store']);
Route::put('channels/{channel}', [ChannelController::class, 'update']);
Route::delete('channels/{channel}', [ChannelController::class, 'destroy']);
Route::get('channels/list', [ChannelController::class, 'getchannels']);
Route::get('channels/{channel}', [ChannelController::class, 'show']);



Route::get('size', [SizeController::class, 'index'])->name('size');



Route::get('tailor', [TailorController::class, 'index'])->name('tailor');
Route::post('add_tailor', [TailorController::class, 'add_tailor'])->name('add_tailor');
Route::get('show_tailor', [TailorController::class, 'show_tailor'])->name('show_tailor');
Route::post('edit_tailor', [TailorController::class, 'edit_tailor'])->name('edit_tailor');
Route::post('update_tailor', [TailorController::class, 'update_tailor'])->name('update_tailor');
Route::post('delete_tailor', [TailorController::class, 'delete_tailor'])->name('delete_tailor');


Route::get('resale', [ResaleController::class, 'index'])->name('resale');
Route::post('add_resale', [ResaleController::class, 'add_resale'])->name('add_resale');
Route::get('show_resale', [ResaleController::class, 'show_resale'])->name('show_resale');
Route::post('edit_resale', [ResaleController::class, 'edit_resale'])->name('edit_resale');
Route::post('update_resale', [ResaleController::class, 'update_resale'])->name('update_resale');
Route::post('delete_resale', [ResaleController::class, 'delete_resale'])->name('delete_resale');

Route::post('get_images', [ResaleController::class, 'get_images'])->name('get_images');
Route::get('show_images', [ResaleController::class, 'show_images'])->name('show_images');
Route::post('upload_img', [ResaleController::class, 'upload_img'])->name('upload_img');
Route::delete('delete_image', [ResaleController::class, 'delete_image'])->name('delete_image');
Route::delete('del_img', [ResaleController::class, 'del_img'])->name('del_img');

Route::get('material', [MaterialController::class, 'index'])->name('material');
Route::post('add_material', [MaterialController::class, 'add_material'])->name('add_material');
Route::get('material/list', [MaterialController::class, 'getmaterial']);
Route::get('edit_material/{id}', [MaterialController::class, 'edit_material'])->name('edit_material');
Route::post('update_material', [MaterialController::class, 'update_material'])->name('update_material');
Route::delete('/delete_material/{id}', [MaterialController::class, 'delete_material'])->name('delete_material');
Route::get('view_material', [MaterialController::class, 'view_material'])->name('view_material');

Route::get('stock', [StockController::class, 'index'])->name('stock');
Route::post('add_stock', [StockController::class, 'add_stock'])->name('add_stock');
Route::get('view_stock', [StockController::class, 'view_stock'])->name('view_stock');
Route::post('update_stock', [StockController::class, 'update_stock'])->name('update_stock');
Route::delete('/delete_stock/{id}', [StockController::class, 'delete_stock'])->name('delete_stock');

Route::get('stock/list', [StockController::class, 'getstock']);
Route::get('stock/{id}', [StockController::class, 'show'])->name('stock.show');
Route::get('edit_stock/{id}', [StockController::class, 'edit_stock'])->name('edit_stock');
Route::delete('/stock/image/{id}', [StockController::class, 'deleteImage'])->name('stock.image.delete');

Route::get('/fetch_stock/{id}', [StockController::class, 'fetch_stock']);
Route::delete('stock/{id}', [StockController::class, 'destroy'])->name('stock.destroy');
Route::get('stock_detail', [StockController::class, 'stock_detail'])->name('stock_detail');
Route::get('get_stock_quantity', [StockController::class, 'get_stock_quantity'])->name('get_stock_quantity');
Route::get('get_full_stock_details', [StockController::class, 'get_full_stock_details'])->name('get_full_stock_details');
Route::post('add_quantity', [StockController::class, 'add_quantity'])->name('add_quantity');

Route::get('branch', [BranchController::class, 'index'])->name('branch');
Route::post('add_branch', [BranchController::class, 'add_branch'])->name('add_branch');
Route::get('show_branch', [BranchController::class, 'show_branch'])->name('show_branch');
Route::post('edit_branch', [BranchController::class, 'edit_branch'])->name('edit_branch');
Route::post('update_branch', [BranchController::class, 'update_branch'])->name('update_branch');
Route::post('delete_branch', [BranchController::class, 'delete_branch'])->name('delete_branch');

Route::get('send_request', [SpecialOrderController::class, 'send_request'])->name('send_request');
Route::get('send_request/data', [SpecialOrderController::class, 'getTailorAssignmentsData'])->name('send_request.data');
Route::post('send_request/assign', [SpecialOrderController::class, 'assignItemsToTailor'])->name('send_request.assign');
Route::post('send_request/receive', [SpecialOrderController::class, 'markTailorItemsReceived'])->name('send_request.receive');

Route::get('spcialorder', [SpecialOrderController::class, 'index'])->name('spcialorder');
Route::post('add_spcialorder', [SpecialOrderController::class, 'add_specialorder'])->name('add_spcialorder');
Route::get('view_special_order', [SpecialOrderController::class, 'view_special_order'])->name('view_special_order');
Route::get('get_orders_list', [SpecialOrderController::class, 'getOrdersList'])->name('get_orders_list');
Route::post('record_payment', [SpecialOrderController::class, 'recordPayment'])->name('record_payment');
Route::post('update_delivery_status', [SpecialOrderController::class, 'updateDeliveryStatus'])->name('update_delivery_status');
Route::post('delete_order', [SpecialOrderController::class, 'deleteOrder'])->name('delete_order');
Route::post('edit_spcialorder', [SpecialOrderController::class, 'edit_spcialorder'])->name('edit_spcialorder');
Route::post('update_spcialorder', [SpecialOrderController::class, 'update_spcialorder'])->name('update_spcialorder');
Route::post('delete_spcialorder', [SpecialOrderController::class, 'delete_spcialorder'])->name('delete_spcialorder');
Route::get('search_abayas', [SpecialOrderController::class, 'searchAbayas'])->name('search_abayas');

Route::get('wharehouse', [WharehouseController::class, 'index'])->name('wharehouse');
Route::post('add_wharehouse', [WharehouseController::class, 'add_wharehouse'])->name('add_wharehouse');
Route::get('show_wharehouse', [WharehouseController::class, 'show_wharehouse'])->name('show_wharehouse');
Route::post('edit_wharehouse', [WharehouseController::class, 'edit_wharehouse'])->name('edit_wharehouse');
Route::post('update_wharehouse', [WharehouseController::class, 'update_wharehouse'])->name('update_wharehouse');
Route::post('delete_wharehouse', [WharehouseController::class, 'delete_wharehouse'])->name('delete_wharehouse');
Route::get('manage_quantity', [WharehouseController::class, 'manage_quantity'])->name('manage_quantity');
Route::get('get_inventory', [WharehouseController::class, 'get_inventory'])->name('get_inventory');
Route::get('get_channel_inventory', [WharehouseController::class, 'get_channel_inventory'])->name('get_channel_inventory');
Route::post('execute_transfer', [WharehouseController::class, 'execute_transfer'])->name('execute_transfer');
Route::get('get_transfer_history', [WharehouseController::class, 'get_transfer_history'])->name('get_transfer_history');
Route::get('export_transfers_excel', [WharehouseController::class, 'export_transfers_excel'])->name('export_transfers_excel');
Route::get('get_channel_stocks', [WharehouseController::class, 'get_channel_stocks'])->name('get_channel_stocks');
Route::get('get_stats', [WharehouseController::class, 'get_stats'])->name('get_stats');
Route::get('settlement', [WharehouseController::class, 'settlement'])->name('settlement');

