<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Transaction\InfoFormRequest as TransactionInfoFormRequest;
use App\Http\Requests\Admin\Transaction\ListFormRequest as TransactionListFormRequest;
use App\Http\Resources\Admin\TransactionResource;
use App\Models\Transaction;
use App\Queriplex\TransactionQueriplex;
use Pylon\Exports\BasicExcelExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function list(TransactionListFormRequest $request)
    {
        $payload = $request->validated();

        $payload['sort_by'] = 'created_time';

        $transactions = TransactionQueriplex::make(Transaction::query(), $payload)
            ->paginate($payload['items_per_page'] ?? 15);

        $transactions->load(['user', 'merchant']);

        $result = TransactionResource::paginateCollection($transactions);

        $response = [
            'transactions' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function info(TransactionInfoFormRequest $request)
    {
        $payload = $request->validated();

        $transaction = TransactionQueriplex::make(Transaction::query(), $payload)
            ->withTrashed()
            ->firstOrThrowError();

        $transaction->load([]);

        $result = new TransactionResource($transaction);

        $response = [
            'transaction' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function topupList(TransactionListFormRequest $request)
    {
        $payload = $request->validated();
        $transactions = TransactionQueriplex::make(Transaction::query(), $payload)
            ->where('type', Transaction::TYPE_TOPUP)
            ->paginate($payload['items_per_page'] ?? 15);

        $transactions->load(['user', 'merchant']);

        $result = TransactionResource::paginateCollection($transactions);

        $response = [
            'topup' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function withdrawList(TransactionListFormRequest $request)
    {
        $payload = $request->validated();
        $transactions = TransactionQueriplex::make(Transaction::query(), $payload)
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->paginate($payload['items_per_page'] ?? 15);

        $transactions->load(['user', 'merchant']);

        $result = TransactionResource::paginateCollection($transactions);

        $response = [
            'withdraw' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function export(TransactionListFormRequest $request)
    {
        $payload = $request->validated();

        $transactions = TransactionQueriplex::make(Transaction::query(), $payload)
            ->get();

        $transactions->load(['user', 'merchant']);
        $result = $transactions->map(function ($transaction) {
            return [
                'ID' => $transaction->id,
                'User' => $transaction->user->name,
                'Credit' => $transaction->credit,
                'Type' => $transaction->type,
                'Promotion Credit' => $transaction->promotion_credit,
                'Payment Method' => $transaction->payment_method,
                'Merchant' => $transaction->merchant->name,
                'Created At' => $transaction->created_at->format('Y-m-d H:i:s'),
            ];
        });


        $fileName = now()->format('Ymdhms') . "-Transaction-List.xlsx";
        log::info(Excel::download(new BasicExcelExport($result), $fileName));
        return Excel::download(new BasicExcelExport($result), $fileName);
    }
}
