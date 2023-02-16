<?php


namespace App\API\V1\Services\orders;


use App\Models\DiagnosticOrderLab;
use App\Models\DiagnosticOrderRad;
use App\Models\KardexLaboratory;
use App\Models\KardexMedication;
use App\Models\KardexRadiology;
use App\Models\MedsOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{


    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  int                                  $numDigits
     *
     * @return string
     */
    public function generateSysId(Model $model, $numDigits = 11)
    {
        try {
            $numDigits += 4;
            $now = date('Y');
            $first = str_pad($now, $numDigits, '0', STR_PAD_RIGHT);
            $last = str_pad($now, $numDigits, '9', STR_PAD_RIGHT);

            $priKey = $model->getKeyName();
            $tableName = $model->getTable();

            $sql
                = "SELECT MAX({$priKey}) FROM {$tableName} WHERE {$priKey} BETWEEN '{$first}' AND '{$last}'";

            $max = DB::select(DB::raw($sql));

            return bcadd($max ?: $first, 1);
        } catch (Exception $e) {
            // Failed to execute query or the value is null
            throw new Exception('Error in generating id: '.$e->getMessage());
        }
    }


    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return bool|string
     */
    public function generateRefNo(Model $model): string
    {
        try {
            switch ($model) {
                case ($model instanceOf DiagnosticOrderLab):
                    $kardex = new KardexLaboratory();
                    break;
                case ($model instanceOf DiagnosticOrderRad):
                    $kardex = new KardexRadiology();
                    break;
                default:
                    break;
            }
            if ($kardex) {
                throw new Exception('Model not found');
            }

            $batch = $model->orderBatch;
            $orders = $model::where('orderbatch_id', $batch->id);

            if ( ! empty($orders)) {
                foreach ($orders as $order) {
                    $kardex = $order->kardex;
                    if ( ! empty($kardex)) {
                        $refno = $kardex->refno;
                    }
                }
            }

            return ! empty($refno) ? $refno : null;

        } catch (Exception $e) {
            return $e->getMessage();
        }

    }
}