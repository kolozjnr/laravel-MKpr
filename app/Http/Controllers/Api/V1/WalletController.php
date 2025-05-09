<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use App\Models\InitializeDeposit;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repository\IWalletRepository;
use App\Notifications\WalletFundedNotification;

class WalletController extends Controller
{
    protected $walletRepository;

    public function __construct(IWalletRepository $walletRepository)
    {
        $this->walletRepository = $walletRepository;
    }

    /**
     * Fund the user's wallet.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    
     public function initializePayment(Request $request)
    {
        \Log::info('Method:', [request()->method()]);

        //dd($request->all());
        $request->validate([
            'amount' => 'required|numeric|min:100', 
        ]);

        $userId = Auth::id();
        $amount = $request->input('amount');
        
        try {
            $paymentData = $this->walletRepository->initializePayment($userId, $amount);
            //yo man, lets go to space 🚀 with KolozJNR
            //d($paymentData);
            $transaction = InitializeDeposit::create([
                'user_id' => $userId,
                'reference' => $paymentData['data']['reference'],
                'amount' => $amount,
                'status' => 'pending',
                'trx' => InitializeDeposit::generateTrx(10),
            ]);

            return response()->json(['message' => 'Payment initialized successfully!', 'data' => $paymentData], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify a Paystack payment and fund the wallet.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment($reference)
    {
        //dd($reference);

        $user = Auth::user();

        try {
            if (InitializeDeposit::where('reference', $reference)->where('status', 'successful')->exists()) {
                throw new Exception("Transaction already processed.");
            }else{

           
    
            $paymentData = $this->walletRepository->verifyPayment($reference);
            InitializeDeposit::where('reference', $reference)->update([
                'status' => 'successful',
                'reference' => $paymentData['data']['reference'],
                'token' => $paymentData['data']['authorization']['authorization_code'],
                'method' => $paymentData['data']['channel'],
                'currency' => $paymentData['data']['currency'],
                'amount' => $paymentData['data']['amount'],
            ]);
            return response()->json(['message' => 'Payment verified and wallet funded successfully!', 'data' => $paymentData], 200);

            $user->notify(new WalletFundedNotification($paymentData));

        }
        } catch (Exception $e) {
            initializeDeposit::where('reference', $reference)->update(['status' => 'failed']);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get the user's wallet balance.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance()
    {
        $userId = Auth::id();

        try {
            $balance = $this->walletRepository->getBalance($userId);
            return response()->json(['balance' => $balance], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
