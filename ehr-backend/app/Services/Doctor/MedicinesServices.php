<?php
/**
 * Created by PhpStorm.
 * User: Leira
 * Date: 10/5/2019
 * Time: 4:28 PM
 */

namespace App\Services\Doctor;

use App\Exceptions\EhrException\EhrException;
use App\Models\HIS\HisPharmaItemsCf4;
use App\Models\HIS\HisPharmaOutsideOrder;
use App\Models\HIS\HisPharmaProductsMain;
use Illuminate\Support\Facades\DB;

class MedicinesServices
{
    /**
     * @var \Illuminate\Database\ConnectionInterface $hisDb
     */
    public $hisDb;

    public function __construct()
    {
        $this->hisDb =  DB::connection('his_mysql');
    }

    public function medication($encounterNo)
    {
        $sql = "SELECT
        items.refno,
        pharma.drug_code,
        SUM(items.quantity - IFNULL(spri.quantity, 0)) AS quantity,
        (
            CASE WHEN orders.is_cash
            THEN items.pricecash
            ELSE items.pricecharge END
        ) as total_cost,
        pharma.generic AS generic_name,
        med.gen_code,
        med.form_code,
        med.salt_code,
        med.package_code,
        med.strength_code,
        med.unit_code,
        items.bestellnum AS item_id,
        orders.`encounter_nr`,
        'INSIDE' AS meds,
        orders.`orderdate`,
        items.`pharma_area`,
        orders.`create_id`,
        orders.`modify_id`,
        user.personell_nr as modified_by
      FROM `seg_pharma_order_items` `items`
      LEFT JOIN `seg_pharma_orders` `orders`
        ON items.refno=orders.refno
      LEFT JOIN `care_users` `user`
        ON user.login_id=orders.`create_id`
      LEFT JOIN `care_pharma_products_main` `pharma`
        ON pharma.bestellnum=items.bestellnum
      LEFT JOIN `seg_phil_medicine` `med`
        ON med.drug_code = pharma.drug_code
      LEFT JOIN
        (SELECT
            rd.ref_no,
            'Return' AS source,
            rd.bestellnum,
            SUM(quantity) AS quantity
        FROM seg_pharma_return_items AS rd
        INNER JOIN seg_pharma_returns AS rh
          ON rd.return_nr = rh.return_nr
          AND rh.encounter_nr='{$encounterNo}'
      WHERE EXISTS
        (SELECT * FROM seg_pharma_orders AS oh
          WHERE encounter_nr='{$encounterNo}'
            AND rd.ref_no = oh.refno)
          GROUP BY rd.ref_no,
            rd.bestellnum) AS spri
            ON items.refno = spri.ref_no
            AND items.bestellnum = spri.bestellnum
          WHERE orders.encounter_nr='{$encounterNo}'
            AND items.serve_status = 'S'
            AND pharma.prod_class= 'M'
            AND items.is_deleted = 0
            AND items.returns = 0
            AND orders.is_deleted = 0
            AND (items.quantity - IFNULL(spri.quantity, 0)) > 0
            GROUP BY items.refno, items.bestellnum
            ORDER BY orders.orderdate DESC";

        return collect($this->hisDb->select($sql))->recursive()->toArray();

    }

    public function outsideMedicationUI($encounterNo)
    {
        $sql = "SELECT
          (SELECT
            spmg.`gen_description`
          FROM
            seg_phil_medecine_generic spmg
            LEFT JOIN seg_phil_medicine AS spm
              ON spm.`gen_code` = spmg.`gen_code`
          WHERE spm.`drug_code` = IF(
              cpoo.gen_code IS NULL,
              cpoo.drug_code,
              cppm.`drug_code`
            )) AS generic_name,
            cpoo.brand_name,
          (SELECT
            strength_code
          FROM
            seg_phil_medicine AS spm
          WHERE spm.`drug_code` = IF(
              cpoo.gen_code IS NULL,
              cpoo.drug_code,
              cppm.`drug_code`
            )) AS strength_code,
          IF(
            cpoo.gen_code IS NULL,
            cpoo.drug_code,
            cppm.`drug_code`
          ) AS drug_code,
          IF(
            cpoo.gen_code IS NULL,
            (SELECT
              mf.`form_desc`
            FROM
              seg_phil_medicine_form AS mf
              LEFT JOIN seg_phil_medicine AS m
                ON mf.form_code = m.form_code
            WHERE m.drug_code = IF(
                cpoo.gen_code IS NULL,
                cpoo.drug_code,
                cppm.`drug_code`
              )),
            (SELECT
              mf.`form_desc`
            FROM
              seg_phil_medicine_form AS mf
              LEFT JOIN seg_phil_medicine AS m
                ON mf.form_code = m.form_code
            WHERE m.drug_code = cppm.`drug_code`)
          ) AS form_desc,
          cpoo.`price` as total_cost,
          cpoo.`quantity`,
          cpoo.`gen_code` AS item_id,
          cpoo.frequency,
          cpoo.route,
          'OUTSIDE' AS meds,
          cpoo.`dosage`,
          cpoo.`order_dt` AS orderdate,
          cpoo.id AS refno,
          cpoo.create_id,
          cpoo.modify_id,
          user.personell_nr as modified_by
        FROM
          care_pharma_outside_order AS cpoo
          LEFT JOIN `care_users` `user`
            ON user.login_id=cpoo.`create_id`
          LEFT JOIN `care_pharma_products_main` AS cppm
            ON cppm.bestellnum = cpoo.gen_code
        WHERE encounter_nr = '{$encounterNo}' AND cpoo.`is_deleted` = '0'
        ORDER BY cpoo.`order_dt` ASC";

        return collect($this->hisDb->select($sql))->recursive()->toArray();

    }

    public function ParentEncounter($encounterNo)
    {
        // $command = $this->hisDb->createCommand();
        // $command->select('parent_encounter_nr');
        // $command->from('care_encounter');
        // $command->where('encounter_nr=:encounter');
        // $command->params[':encounter'] = $encounterNo;

        // $parent_nr = $command->queryAll();

        // return $parent_nr[0]['parent_encounter_nr'];
    }

    public function FinalBillStatus($encounterNo)
    {
        // $command = $this->hisDb->createCommand();
        // $command->select('is_final');
        // $command->from('seg_billing_encounter');
        // $command->where('encounter_nr=:encounter AND (is_deleted IS NULL OR is_deleted=:is_deleted)');
        // $command->params[':encounter'] = $encounterNo;
        // $command->params[':is_deleted'] = 0;

        // $bill = $command->queryAll();

        // return $bill[0]['is_final'];
    }

    public function medicine_details($refno, $item_id)
    {
        return HisPharmaItemsCf4::query()->where('refno', $refno)->where('bestellnum', $item_id)->first();
    }

    public function getInsideMedicines($bestellnum)
    {
        $model = HisPharmaProductsMain::query()->select('generic')->find($bestellnum);
        return $model ? $model : null;
    }

    public function outsideMedication($encounterNo)
    {
        $sql = "Select
              (SELECT
                spmg.`gen_description`
              FROM
                seg_phil_medecine_generic spmg
                LEFT JOIN seg_phil_medicine AS spm
                  ON spm.`gen_code` = spmg.`gen_code`
              WHERE spm.`drug_code` = IF(
                  cpoo.gen_code IS NULL,
                  cpoo.drug_code,
                  cppm.`drug_code`
                )) AS generic_name,
                cpoo.brand_name,
              (SELECT
                strength_code
              FROM
                seg_phil_medicine AS spm
              WHERE spm.`drug_code` = IF(
                  cpoo.gen_code IS NULL,
                  cpoo.drug_code,
                  cppm.`drug_code`
                )) AS strength_code,
              IF(
                cpoo.gen_code IS NULL,
                cpoo.drug_code,
                cppm.`drug_code`
              ) AS drug_code,
              IF(
                cpoo.gen_code IS NULL,
                (SELECT
                  mf.`form_desc`
                FROM
                  seg_phil_medicine_form AS mf
                  LEFT JOIN seg_phil_medicine AS m
                    ON mf.form_code = m.form_code
                WHERE m.drug_code = IF(
                    cpoo.gen_code IS NULL,
                    cpoo.drug_code,
                    cppm.`drug_code`
                  )),
                (SELECT
                  mf.`form_desc`
                FROM
                  seg_phil_medicine_form AS mf
                  LEFT JOIN seg_phil_medicine AS m
                    ON mf.form_code = m.form_code
                WHERE m.drug_code = cppm.`drug_code`)
              ) AS form_desc,
              cpoo.`price` as total_cost,
              cpoo.`quantity`,
              cpoo.`gen_code`,cpoo.frequency,cpoo.route,
              'OUTSIDE' AS meds,
              cpoo.`dosage`
            FROM
              care_pharma_outside_order AS cpoo
              LEFT JOIN `care_pharma_products_main` AS cppm
                ON cppm.bestellnum = cpoo.gen_code
            WHERE encounter_nr = '{$encounterNo}' AND cpoo.`is_deleted` = '0'
            ORDER BY cpoo.`order_dt` ASC";

        return collect($this->hisDb->select($sql))->recursive()->toArray();

    }

    public function getHISRefItem($ref, $bestellnum)
    {

        $sql = "Select
                  spoi.`refno`,
                  spoi.`bestellnum` AS item_id,
                  spoi.`quantity` AS quantity,
                  spic.`frequency` AS instruction,
                  spic.`route` AS route,
                  cppm.`generic` AS generic_name,
                  spoi.`price_orig` AS price,
                  spoi.`pharma_area` AS pharma_area,
                  cppm.`drug_code` AS drug_code,
                  spo.`encounter_nr`,
                  'INSIDE' AS meds
                FROM
                  `seg_pharma_orders` AS spo
                  LEFT JOIN `seg_pharma_order_items` AS spoi
                    ON spo.`refno` = spoi.`refno`
                   LEFT JOIN `care_pharma_products_main` AS cppm
                   ON cppm.`bestellnum` = spoi.`bestellnum`
                  LEFT JOIN seg_phil_medicine AS spm
                    ON cppm.`drug_code` = spm.`drug_code`
                  LEFT JOIN `seg_phil_medicine_form` AS spmf
                    ON spmf.`form_code` = spm.`form_code`
                  LEFT JOIN `seg_phil_medecine_generic` AS spmg
                    ON spmg.`gen_code` = spm.`gen_code`
                  LEFT JOIN `seg_pharma_items_cf4` AS spic
                    ON spic.`refno` = spo.`refno`
                    AND spic.`bestellnum` = spoi.`bestellnum`
                WHERE spoi.refno = '{$ref}'
                AND spoi.bestellnum = '{$bestellnum}'
                AND cppm.`prod_class` = 'M'
                ORDER BY spo.`refno` ASC";

        return collect($this->hisDb->select($sql))->recursive()->toArray();
    }



    public function onSaveFreqRoute($data){

      $data = collect($data);
      if($data->get('source') == 'INSIDE'){
        $res =  HisPharmaItemsCf4::query()->where('refno', $data->get('refno'))->where('bestellnum', $data->get('item_code'))->first();
        if ($res) {
          $res->history = "Update: User ID(". $data->get('user_id') . ") - " . date('m-d-Y H:i:s') . "\n". $res->history;
        }else{
          $res = new HisPharmaItemsCf4();
          $res->bestellnum = $data->get('item_code');
          $res->refno = $data->get('refno');
          $res->history = "Created: User ID(". $data->get('user_id'). ") - ". date('m-d-Y H:i:s');
          $res->create_id = $data->get('user_id');
          $res->create_dt = date('Y-m-d H:i:s');
        }

        $res->frequency = $data->get('frequency','');
        $res->route = $data->get('route','');
        $res->dosage = $data->get('dosage','');
        $res->modify_id = $data->get('user_id');
        $res->modify_dt = date('Y-m-d H:i:s');
        return $res->save();
      }
      else{
        $check =  HisPharmaOutsideOrder::query()->find($data->get('refno'));
        if($check){
          $check->frequency = $data->get('frequency');
          $check->route = $data->get('route','');
          $check->dosage = $data->get('dosage','');
          $check->modify_id = auth()->user()->username;
          $check->modify_dt = date('Y-m-d H:i:s');
          return $check->save();
        }

        throw new EhrException("Item does not exist as outside meds.",404);
      }
    }
}
