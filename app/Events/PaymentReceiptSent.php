<?php

// namespace App\Events;

// use App\Models\Transaction;
// use Illuminate\Broadcasting\Channel;
// use Illuminate\Queue\SerializesModels;
// use Illuminate\Broadcasting\PrivateChannel;
// use Illuminate\Broadcasting\PresenceChannel;
// use Illuminate\Foundation\Events\Dispatchable;
// use Illuminate\Broadcasting\InteractsWithSockets;
// use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// class PaymentReceiptSent implements ShouldBroadcast
// {
//     use Dispatchable, InteractsWithSockets, SerializesModels;

//     public $transaction;

//     /**
//      * Create a new event instance.
//      */
//     public function __construct(
//           Transaction $transaction)
//     {
//         $this->transaction = $transaction;
//     }

//     /**
//      * Get the channels the event should broadcast on.
//      *
//      * @return array<int, \Illuminate\Broadcasting\Channel>
//      */
//     public function broadcastOn(): Channel
//     {
//         return new Channel('payment.' . $this->transaction->transaction_id);  
//         // return [
//         //     new PrivateChannel('payment.' . $this->transaction->transaction_id),
//         // ];
//     }

//     /**
//      * The event's broadcast name.
//      */
//     public function broadcastAs(): string
//     {
//         return 'payment.received';
//     }

//     /**
//      * Get the data to broadcast.
//      *
//      * @return array<string, mixed>
//      */
//     public function broadcastWith(): array
//     {
//         return [
//             'id' => $this->transaction->id,
//             'transaction_id' => $this->transaction->transaction_id,
//             'status' => $this->transaction->status,
//             'amount' => $this->transaction->amount,
//             'currency' => $this->transaction->currency,
//             'payment_type' => $this->transaction->description,
//             'additional_data' => $this->getAdditionalData(),
//         ];
//     }

//     /**
//      * Get additional data based on the payment type.
//      */
//     private function getAdditionalData(): array
//     {
//         switch ($this->transaction->description) {
//             case 'product_sale':
//                 $product = $this->transaction->product;
//                 return [
//                     'product_name' => $product->name,
//                     'product_access_link' => $product->access_link,
//                     'product_image' => $product->image,
//                 ];
//             case 'signup_fee':
//                 $user = $this->transaction->user;
//                 return [
//                     'user_name' => $user->name,
//                     'user_email' => $user->email,
//                     'aff_id' => $user->aff_id,
//                 ];
//             case 'market_access':
//                 $user = $this->transaction->user;
//                 return [
//                     'user_name' => $user->name,
//                     'market_access' => $user->market_access,
//                 ];
//             default:
//                 return [];
//         }
//     }
// }
