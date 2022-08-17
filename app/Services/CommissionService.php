<?php

namespace App\Services;

use App\Exceptions\InvalidOperationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CommissionService
{
    private const KEYS = [
        'date',
        'user_id',
        'user_type',
        'operation_type',
        'amount',
        'currency',
    ];

    private const DATE_FORMAT = 'Y-m-d';

    private const USER_TYPE_PRIVATE = 'private';
    private const USER_TYPE_BUSINESS = 'business';

    private const OPERATION_TYPE_DEPOSIT = 'deposit';
    private const OPERATION_TYPE_WITHDRAW = 'withdraw';

    /**
     * Keys count in the memory to not calculate with every row.
     *
     * @var int
     */
    private int $keysCount;

    /**
     * Detailed currency list
     *
     * @var array
     */
    private array $currencies;

    /**
     * Keeping currency slugs in the memory to not run array_keys function with every row.
     *
     * @var array
     */
    private array $currencySlugs;

    /**
     * History of free withdrawals.
     *
     * @var array
     */
    private array $freeWithdrawalHistory = [];

    /**
     * Deposit fee
     *
     * @var float
     */
    private float $depositFee;

    /**
     * Detailed fees of withdrawals
     *
     * @var array
     */
    private array $withdrawFees;

    /**
     * Constructor of the service
     */
    public function __construct()
    {
        $this->keysCount = count(self::KEYS);

        $this->currencies = config('wallet.currencies', []);
        $this->currencySlugs = array_keys($this->currencies);

        $this->depositFee = config('wallet.fees.deposit', 0.03);
        $this->withdrawFees = config('wallet.fees.withdraw', []);
    }

    /**
     * Calculate single operation fee.
     *
     * @param array $operation
     * @return string
     * @throws InvalidOperationException
     */
    public function calculateOperationFee(array $operation): string
    {
        $inputs = $this->validateOperation($operation);
        if ($inputs['operation_type'] === self::OPERATION_TYPE_DEPOSIT) {
            $fee = $this->calculateDepositFee($inputs);
        } else {
            $fee = $this->calculateWithdrawFee($inputs);
        }

        return $this->formatAmount($fee, $inputs['currency']);
    }

    /**
     * Format amount to preferred format.
     *
     * @param $fee
     * @param $currency
     * @return string
     */
    private function formatAmount($fee, $currency): string
    {
        $decimals = $this->currencies[$currency]['decimals'];
        $multiplication = pow(10, $decimals);
        $float = ceil($fee * $multiplication) / $multiplication;
        return number_format($float, $decimals, '.', '');
    }

    /**
     * Calculate deposit fee
     *
     * @param $inputs
     * @return float|int
     */
    private function calculateDepositFee($inputs): float|int
    {
        return $inputs['amount'] * $this->depositFee / 100;
    }

    /**
     * Calculate withdrawal fee
     *
     * @param $inputs
     * @return float|int
     */
    private function calculateWithdrawFee($inputs): float|int
    {
        $fees = $this->withdrawFees[$inputs['user_type']];
        $remainedAmount = $this->subFreeAmount($inputs, $fees);

        return $remainedAmount > 0 ? $remainedAmount * $fees['commission'] / 100 : 0;
    }

    /**
     * Sub free amount available for user
     *
     * @param array $inputs
     * @param array $fees
     * @return float
     */
    private function subFreeAmount(array $inputs, array $fees): float
    {
        $rate = $this->currencies[$inputs['currency']]['rate'];

        $localAmount = (float)$inputs['amount'];
        // Convert to EUR
        $amount = $localAmount / $rate;
        if ($fees['free_amount'] <= 0 && $fees['free_count'] <= 0) {
            return $localAmount;
        }
        $startOfWeek = Carbon::createFromFormat(self::DATE_FORMAT, $inputs['date'])->startOfWeek()->format(self::DATE_FORMAT);
        $userId = (int)$inputs['user_id'];
        $history = $this->getFreeWithdrawalHistory($userId, $startOfWeek);
        if ($history['count'] >= $fees['free_count']) {
            return $localAmount;
        }
        $remainedFreeAmount = $fees['free_amount'] - $history['amount'];
        if ($remainedFreeAmount <= 0) {
            return $localAmount;
        }
        $freeAmount = min($remainedFreeAmount, $amount);
        $this->addFreeWithdrawalHistory($userId, $startOfWeek, $freeAmount);

        // Convert back to local currency
        return ($amount - $freeAmount) * $rate;
    }

    /**
     * Get free withdrawal history of user
     *
     * @param int $userId
     * @param string $week
     * @return array
     */
    private function getFreeWithdrawalHistory(int $userId, string $week): array
    {
        return $this->freeWithdrawalHistory[$userId][$week] ?? [
            'amount' => 0,
            'count' => 0,
        ];
    }

    /**
     * Add free withdrawal operation to user history
     *
     * @param int $userId
     * @param string $week
     * @param float $amount
     * @return void
     */
    private function addFreeWithdrawalHistory(int $userId, string $week, float $amount): void
    {
        $history = $this->getFreeWithdrawalHistory($userId, $week);
        $history['amount'] += $amount;
        ++$history['count'];
        $this->freeWithdrawalHistory[$userId][$week] = $history;
    }

    /**
     * Validate the operation
     *
     * @throws InvalidOperationException
     */
    private function validateOperation(array $operation): array
    {
        if (count($operation) !== $this->keysCount) {
            throw new InvalidOperationException('Invalid operation.');
        }

        $inputs = array_combine(self::KEYS, $operation);

        $validator = Validator::make($inputs, [
            'date' => [
                'required',
                'date_format:' . self::DATE_FORMAT,
            ],
            'user_id' => [
                'required',
                'integer',
            ],
            'user_type' => [
                'required',
                Rule::in([self::USER_TYPE_PRIVATE, self::USER_TYPE_BUSINESS]),
            ],
            'operation_type' => [
                'required',
                Rule::in([self::OPERATION_TYPE_DEPOSIT, self::OPERATION_TYPE_WITHDRAW]),
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0'
            ],
            'currency' => [
                'required',
                'string',
                Rule::in($this->currencySlugs),
            ],
        ]);

        $validator->stopOnFirstFailure();

        if ($validator->fails()) {
            throw new InvalidOperationException('Invalid operation, error: ' . $validator->errors()->first());
        }

        return $inputs;
    }
}
