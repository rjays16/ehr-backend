<?php


namespace App\Services\HIS\HisPhicCoverage;


use App\Models\Encounter;
use Illuminate\Support\Facades\DB;

class HisDiscountService
{

    /**
     * @var \App\Models\Encounter
     */
    public $encounter;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    public $pdo;

    /**
     * @var
     */
    public $is_senior;

    /**
     * @var
     */
    public $is_pwd;

    /**
     * @var
     */
    public $walkinDiscount;

    /**
     * @var
     */
    public $sc_walkin_discount;

    /**
     * @var
     */
    public $non_social_discount;

    /**
     * @var
     */
    public $parentDiscount;

    /**
     * @var
     */
    public $is_walkin;

    /**
     * @var
     */
    public $non_social;

    /**
     * @var
     */
    public $discount_non_social;

    public $discountId;

    /**
     * HisDiscountService constructor.
     * @param \App\Models\Encounter|null $encounter
     */
    function __construct(Encounter $encounter = null)
    {
        $this->encounter = $encounter;
        $this->pdo = DB::connection('his_mysql');
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $encounter_no = $this->encounter ? $this->encounter->encounter_no : '';
        $sql = "SELECT
                  IF(
                    ps.nr IS NOT NULL,
                    IF(
                      SUBSTRING(
                        MAX(
                          CONCAT(
                            ce.encounter_date,
                            ce.encounter_nr
                          )
                        ),
                        20
                      ),
                      'PHS',
                      ''
                    ),
                    IF(
                      dep.dependent_pid IS NOT NULL,
                      IF(
                        SUBSTRING(
                          MAX(
                            CONCAT(
                              ce.encounter_date,
                              ce.encounter_nr
                            )
                          ),
                          20
                        ),
                        'PHSDep',
                        ''
                      ),
                      IF(
                        SUBSTRING(
                          MAX(
                            CONCAT(scp.grant_dte, scp.discountid)
                          ),
                          20
                        ) = 'SC',
                        IF(
                          SUBSTRING(
                            MAX(
                              CONCAT(
                                ce.encounter_date,
                                ce.encounter_nr
                              )
                            ),
                            20
                          ),
                          'SC',
                          'SC'
                        ),
                        IF(
                          SUBSTRING(
                            MAX(
                              CONCAT(
                                ce.encounter_date,
                                ce.encounter_nr
                              )
                            ),
                            20
                          ) IS NULL,
                          '',
                          IF(
                            SUBSTRING(
                              MAX(
                                CONCAT(
                                  ce.encounter_date,
                                  ce.encounter_type
                                )
                              ),
                              20
                            ) = 2,
                            SUBSTRING(
                              MAX(
                                CONCAT(scp.grant_dte, scp.discountid)
                              ),
                              20
                            ),
                            SUBSTRING(
                              MAX(
                                CONCAT(scg.grant_dte, scg.discountid)
                              ),
                              20
                            )
                          )
                        )
                      )
                    )
                  ) AS discountid,
                  IF(
                    ps.nr IS NOT NULL,
                    IF(
                      SUBSTRING(
                        MAX(
                          CONCAT(
                            ce.encounter_date,
                            ce.encounter_nr
                          )
                        ),
                        20
                      ),
                      '1',
                      ''
                    ),
                    IF(
                      dep.dependent_pid IS NOT NULL,
                      IF(
                        SUBSTRING(
                          MAX(
                            CONCAT(
                              ce.encounter_date,
                              ce.encounter_nr
                            )
                          ),
                          20
                        ),
                        '1',
                        ''
                      ),
                      IF(
                        SUBSTRING(
                          MAX(
                            CONCAT(scp.grant_dte, scp.discountid)
                          ),
                          20
                        ) = 'SC',
                        IF(
                          SUBSTRING(
                            MAX(
                              CONCAT(
                                ce.encounter_date,
                                ce.encounter_nr
                              )
                            ),
                            20
                          ),
                          '1',
                          '0.20'
                        ),
                        IF(
                          SUBSTRING(
                            MAX(
                              CONCAT(
                                ce.encounter_date,
                                ce.encounter_nr
                              )
                            ),
                            20
                          ) IS NULL,
                          '',
                          IF(
                            SUBSTRING(
                              MAX(
                                CONCAT(
                                  ce.encounter_date,
                                  ce.encounter_type
                                )
                              ),
                              20
                            ) = 2,
                            SUBSTRING(
                              MAX(
                                CONCAT(scp.grant_dte, scp.discount)
                              ),
                              20
                            ),
                            SUBSTRING(
                              MAX(
                                CONCAT(scg.grant_dte, scg.discount)
                              ),
                              20
                            )
                          )
                        )
                      )
                    )
                  ) AS discount,
                  scg.discount_amnt AS discount_amount
                FROM
                  `care_encounter` `ce`
                  JOIN `care_person` `cp`
                    ON cp.pid = ce.pid
                  LEFT JOIN `seg_charity_grants_pid` `scp`
                    ON scp.pid = cp.pid
                    AND scp.status = 'valid'
                    AND scp.discountid NOT IN ('LINGAP')
                  LEFT JOIN `seg_charity_grants_expiry_pid` `scgep`
                    ON scgep.pid = scp.pid
                    AND scp.status = 'valid'
                    AND scgep.grant_dte = scp.grant_dte
                  LEFT JOIN `seg_charity_grants` `scg`
                    ON scg.encounter_nr = ce.encounter_nr
                    AND scg.status = 'valid'
                    AND scg.discountid NOT IN ('LINGAP')
                  LEFT JOIN `seg_charity_grants_expiry` `scge`
                    ON scge.encounter_nr = ce.encounter_nr
                    AND scge.grant_dte = scp.grant_dte
                  LEFT JOIN `care_personell` `ps`
                    ON cp.pid = ps.pid
                    AND (
                      (
                        date_exit NOT IN (DATE(NOW()))
                        AND date_exit > DATE(NOW())
                      )
                      OR date_exit = '0000-00-00'
                      OR date_exit IS NULL
                    )
                    AND (
                      (
                        contract_end NOT IN (DATE(NOW()))
                        AND contract_end > DATE(NOW())
                      )
                      OR contract_end = '0000-00-00'
                      OR contract_end IS NULL
                    )
                    LEFT JOIN `seg_dependents` `dep` ON dep.dependent_pid = cp.pid AND dep.status = 'member'
                    WHERE ce.encounter_nr = '{$encounter_no}'
                    ORDER BY `ce`.`encounter_date` DESC
                    LIMIT 1
                    ";

        return collect($this->pdo->select($sql))->recursive()->first();
    }

    public function computeNetPrice($discountId, $discount, array $service_ids = [])
    {
        if(!$discount)
            $discount = 0;

        $where = "";
        if(!empty($service_ids))
          $where = " AND l.service_code in ('".(implode("','", $service_ids))."') ";

        $this->getLabColumn($discountId, $discount);

        $sql=" SELECT
              l.service_code AS service_id,
              l.group_code AS group_id,
              l.name AS service_name,
              slsg.name AS group_name,
              l.is_socialized,
              l.status,
              l.male_only AS is_for_male,
              l.female_only AS is_for_female,
              l.price_cash,
              l.price_charge,
        IF(gmd.`name_type` != '' ,'','Unavailable') AS is_restricted,
                IF(l.is_socialized = 0,
                IF(($this->non_social),
              (
                l.price_cash * (1- '$this->discount_non_social')
              ),
              IF(
                '$this->is_senior',
                l.price_cash * (1-'$this->sc_walkin_discount'),
                IF(
                  '$this->discountId' = 'PHSDep'
                  OR '$this->discountId' = 'PHS',
                  l.price_cash * (1-'$this->non_social_discount'),
                  l.price_cash
                )
              )
            ),
            IF(
              '$this->is_senior',
              IF(
                '$this->is_walkin',
                (
                  l.price_cash * (1- '$this->sc_walkin_discount')
                ),
                IF(
                  sd.price,
                  sd.price,
                  (l.price_cash * (1- '$discount'))
                )
              ),
              IF(
                '$this->discountId' != '',
                IF(sd.price,sd.price,0),
                (l.price_cash * (1- '$discount'))
              )
            )
          ) AS net_price
            FROM
              `seg_lab_services` `l`
              LEFT JOIN `seg_lab_service_groups` `slsg`
                ON slsg.group_code = l.group_code
              LEFT JOIN `seg_gui_mgr_details` `gmd`
                ON gmd.service_code = l.service_code
              LEFT JOIN `seg_service_discounts` `sd`
                ON sd.service_code = l.service_code
                AND sd.service_area = 'LB'
                AND sd.discountid = '$this->discountId'
            WHERE l.group_code NOT IN ('B', 'SPL', 'SPC', 'CATH', 'ECHO')
              AND l.status NOT IN ('deleted')
              {$where}
            GROUP BY `l`.`service_code`
            ORDER BY `l`.`name` ASC
            ";

        $result = collect($this->pdo->select($sql))->recursive()->all();
        return $result;
    }

    /**
     * @param $discountId
     * @param $discount
     */
    public function getLabColumn($discountId, $discount)
    {
        if($discountId=='SC'){
            $this->is_senior = 1;
        }else{
            $this->is_senior = 0;
        }

        $this->walkinDiscount = 0;
        $this->sc_walkin_discount = 0;

        if($this->is_senior){
            $discountId='SC';
            $this->walkinDiscount = $this->getWalkinDiscount();
            if ($this->walkinDiscount)
                $this->sc_walkin_discount = $this->walkinDiscount;
        }

        $this->non_social_discount = 0;
        if($discountId==='PHSDep'){
            $this->non_social_discount = $this->getPhsDepNonDiscount($discountId);
            if($this->non_social_discount)
                $this->non_social_discount = $this->non_social_discount;
            $discount = $this->getPhsDepDiscount($discountId);
        }

        if($discountId==='PHS'){
            $this->non_social_discount = $this->getPhsNonDiscount($discountId);
            $discount = $this->getPhsDepDiscount($discountId);
        }

        $this->parentDiscount = $this->getParentDiscount($discountId);
        if($this->parentDiscount == "D"){
            $this->discountId = $this->parentDiscount;
        }else{
            $this->discountId = $discountId;
        }

        $this->is_walkin = 1;
        $ExistNonSocial = array("B-PWD","A-PWD","C1-PWD","C2-PWD","C3-PWD", "PWD");

        if(in_array($this->discountId,$ExistNonSocial)){
            $pwd_discount = substr($this->discountId,-3,3);
            $this->non_social = "'$pwd_discount'='PWD'";
            $this->discount_non_social = 0.20;
        }else{
            $this->non_social="l.in_phs=1 AND '$this->discountId'='PHS' ";
            $this->discount_non_social = $discount;
        }

    }

  //   public function computeNetPriceRad($discountId, $discount, array $service_ids = [])
  //   {
  //       if(!$discount)
  //           $discount = 0;

  //       $where = "";
  //       if(!empty($service_ids))
  //         $where = " AND r.service_code in ('".(implode("','", $service_ids))."') ";

  //       $this->getRadColumn($discountId, $discount);

  //       $sql=" SELECT
  //       r.service_code AS service_id,
  //       r.group_code AS group_id,
  //       r.name AS service_name,
  //       srsg.name AS group_name,
  //       r.is_socialized,
  //       r.status,
  //       r.price_cash,
  //       r.price_charge,
  // IF(gmd.`name_type` != '' ,'','Unavailable') AS is_restricted,
  //         IF(r.is_socialized = 0,
  //         IF(($this->non_social),
  //       (
  //         r.price_cash * (1- '$this->discount_non_social')
  //       ),
  //       IF(
  //         '$this->is_senior',
  //         r.price_cash * (1-'$this->sc_walkin_discount'),
  //         IF(
  //           '$this->discountId' = 'PHSDep'
  //           OR '$this->discountId' = 'PHS',
  //           r.price_cash * (1-'$this->non_social_discount'),
  //           r.price_cash
  //         )
  //       )
  //     ),
  //     IF(
  //       ('$this->is_senior' OR '$this->is_pwd'),
  //       IF(
  //           sd2.price,
  //           sd2.price,
  //           IF(
  //               sd.price,
  //               sd.price,
  //               (r.price_cash * (1- '$discount'))
  //           )
  //         ),
  //         IF(
  //               '$this->discountId' != '',
  //               IF(
  //                   sd.price,
  //                   sd.price,
  //                   (r.price_cash * (1- '$discount'))
  //               ),
  //               r.price_cash
  //           )
  //       )
  //   ) AS net_price
  //     FROM
  //       `seg_radio_services` `r`
  //       LEFT JOIN `seg_radio_service_groups` `srsg`
  //         ON srsg.group_code = r.group_code
  //       LEFT JOIN `seg_gui_mgr_details` `gmd`
  //         ON gmd.service_code = r.service_code
  //       LEFT JOIN `seg_service_discounts` `sd`
  //         ON sd.service_code = r.service_code
  //         AND sd.service_area = 'RD'
  //         AND sd.discountid = '$this->discountId'
  //       LEFT JOIN `seg_service_discounts` `sd2`
  //         ON sd2.service_code = r.service_code
  //         AND sd2.service_area = 'RD'
  //         AND sd2.discountid = IF('$this->is_senior', 'SC', IF('$this->is_pwd', 'PWD', '$this->discountId'))
  //     WHERE r.group_code NOT IN ('B', 'SPL', 'SPC', 'CATH', 'ECHO')
  //       AND r.status NOT IN ('deleted')

  //     GROUP BY `r`.`service_code`
  //     ORDER BY `r`.`name` ASC";

  //     dd($sql);

  //       $result = collect($this->pdo->select($sql))->recursive()->all();
  //       return $result;
  //   }

  //   /**
  //    * @param $discountId
  //    * @param $discount
  //    */
  //   public function getRadColumn($discountId, $discount)
  //   {
  //       if($discountId=='SC'){
  //           $this->is_senior = 1;
  //       }else{
  //           $this->is_senior = 0;
  //       }

  //       if($discountId=='PWD'){
  //           $this->is_pwd = 1;
  //       }else{
  //           $this->is_pwd = 0;
  //       }

  //       $this->walkinDiscount = 0;
  //       $this->sc_walkin_discount = 0;

  //       if($this->is_senior){
  //           $discountId='SC';
  //           $this->walkinDiscount = $this->getWalkinDiscount();
  //           if ($this->walkinDiscount)
  //               $this->sc_walkin_discount = $this->walkinDiscount;
  //       }

  //       $this->non_social_discount = 0;
  //       if($discountId==='PHSDep'){
  //           $this->non_social_discount = $this->getPhsDepNonDiscount($discountId);
  //           if($this->non_social_discount)
  //               $this->non_social_discount = $this->non_social_discount;
  //           $discount = $this->getPhsDepDiscount($discountId);
  //       }

  //       if($discountId==='PHS'){
  //           $this->non_social_discount = $this->getPhsNonDiscount($discountId);
  //           $discount = $this->getPhsDepDiscount($discountId);
  //       }

  //       $this->parentDiscount = $this->getParentDiscount($discountId);
  //       if($this->parentDiscount == "D"){
  //           $this->discountId = $this->parentDiscount;
  //       }else{
  //           $this->discountId = $discountId;
  //       }

  //       $this->is_walkin = 1;
  //       $ExistNonSocial = array("B-PWD","A-PWD","C1-PWD","C2-PWD","C3-PWD", "PWD");

  //       if(in_array($this->discountId,$ExistNonSocial) || $this->is_pwd){
  //           $pwd_discount = substr($this->is_pwd ? "PWD" : $this->discountId,-3,3);
  //           $this->non_social = "'$pwd_discount'='PWD'";
  //           $this->discount_non_social = 0.20;
  //       }else{
  //           $this->non_social="r.in_phs=1 AND '$this->discountId'='PHS' ";
  //           $this->discount_non_social = $discount;
  //       }

  //   }

    public function computeNetPriceRad($discountId, $discount, array $service_ids = [])
    {
        if(!$discount)
            $discount = 0;

        $where = "";
        if(!empty($service_ids))
          $where = " AND r.service_code in ('".(implode("','", $service_ids))."') ";

        $this->getRadColumn($discountId, $discount);

        $sql=" SELECT
        r.service_code AS service_id,
        r.group_code AS group_id,
        r.name AS service_name,
        srsg.name AS group_name,
        r.is_socialized,
        r.status,
        r.price_cash,
        r.price_charge,
  IF(gmd.`name_type` != '' ,'','Unavailable') AS is_restricted,
          IF(r.is_socialized = 0,
          IF(($this->non_social),
        (
          r.price_cash * (1- '$this->discount_non_social')
        ),
        IF(
          '$this->is_senior',
          r.price_cash * (1-'$this->sc_walkin_discount'),
          IF(
            '$this->discountId' = 'PHSDep'
            OR '$this->discountId' = 'PHS',
            r.price_cash * (1-'$this->non_social_discount'),
            r.price_cash
          )
        )
      ),
      IF(
        ('$this->parentDiscount' = 'D'),
        IF(
          sd.price,
          sd.price,
          IF(
            sd2.price,
            sd2.price,
            (r.price_cash * (1- '$discount'))
          )
         ),
         IF(
            '$this->discountId' != '',
            IF(
              sd.price,
              sd.price,
              (r.price_cash * (1- '$discount'))
            ),
             r.price_cash
          )
       )
    ) AS net_price
      FROM
        `seg_radio_services` `r`
        LEFT JOIN `seg_radio_service_groups` `srsg`
          ON srsg.group_code = r.group_code
        LEFT JOIN `seg_gui_mgr_details` `gmd`
          ON gmd.service_code = r.service_code
        LEFT JOIN `seg_service_discounts` `sd`
          ON sd.service_code = r.service_code
          AND sd.service_area = 'RD'
          AND sd.discountid = '$this->discountId'
        LEFT JOIN `seg_service_discounts` `sd2`
          ON sd2.service_code = r.service_code
          AND sd2.service_area = 'RD'
          AND sd2.discountid = IF('$this->parentDiscount' = 'D', 'D', '$this->discountId')
      WHERE r.group_code NOT IN ('B', 'SPL', 'SPC', 'CATH', 'ECHO')
        AND r.status NOT IN ('deleted')

      GROUP BY `r`.`service_code`
      ORDER BY `r`.`name` ASC";

      // dd($sql);

        $result = collect($this->pdo->select($sql))->recursive()->all();
        return $result;
    }

    /**
     * @param $discountId
     * @param $discount
     */
    public function getRadColumn($discountId, $discount)
    {
        if($discountId=='SC'){
            $this->is_senior = 1;
        }else{
            $this->is_senior = 0;
        }

        if($discountId=='PWD'){
            $this->is_pwd = 1;
        }else{
            $this->is_pwd = 0;
        }

        $this->walkinDiscount = 0;
        $this->sc_walkin_discount = 0;

        if($this->is_senior){
            $discountId='SC';
            $this->walkinDiscount = $this->getWalkinDiscount();
            if ($this->walkinDiscount)
                $this->sc_walkin_discount = 0;
        }

        $this->non_social_discount = 0;
        if($discountId==='PHSDep'){
            $this->non_social_discount = $this->getPhsDepNonDiscount($discountId);
            if($this->non_social_discount)
                $this->non_social_discount = $this->non_social_discount;
            $discount = $this->getPhsDepDiscount($discountId);
        }

        if($discountId==='PHS'){
            $this->non_social_discount = $this->getPhsNonDiscount($discountId);
            $discount = $this->getPhsDepDiscount($discountId);
        }

        $this->parentDiscount = $this->getParentDiscount($discountId);
        // if($this->parentDiscount == "D"){
        //     $this->discountId = $this->parentDiscount;
        // }else{
        //     $this->discountId = $discountId;
        // }
        $this->discountId = $discountId;

        $this->is_walkin = 1;
        $ExistNonSocial = array("B-PWD","A-PWD","C1-PWD","C2-PWD","C3-PWD", "PWD");

        if(in_array($this->discountId,$ExistNonSocial)){
            $pwd_discount = substr($this->discountId,-3,3);
            $this->non_social = "'$pwd_discount'='PWD'";
            $this->discount_non_social = 0.20;
        }else{
            $this->non_social="r.in_phs=1 AND '$this->discountId'='PHS' ";
            $this->discount_non_social = $discount;
        }

    }

    /**
     * @return mixed
     */
    public function getWalkinDiscount()
    {
        $sql = "
            SELECT
              sdv.value
            FROM
              seg_default_value AS sdv
            WHERE sdv.name = 'senior discount'
              AND sdv.source = 'SS'";

        $result = collect($this->pdo->select($sql))->recursive()->first();
        return $result['value'];
    }

    /**
     * @param $discountId
     * @return mixed
     */
    public function getPhsDepNonDiscount($discountId)
    {
        $sql = "
            SELECT
              sd.non_social_discount
            FROM
              seg_discount AS sd
            WHERE sd.discountid = '{$discountId}'
        ";

        $result = collect($this->pdo->select($sql))->recursive()->first();
        return $result['non_social_discount'];
    }

    /**
     * @param $discountId
     * @return mixed
     */
    public function getPhsDepDiscount($discountId)
    {
        $sql = "
            SELECT
              sd.discount
            FROM
              seg_discount as sd
            WHERE sd.discountid = '{$discountId}'
        ";

        $result = collect($this->pdo->select($sql))->recursive()->first();
        return $result['discount'];
    }

    /**
     * @param $discountId
     * @return mixed
     */
    public function getPhsNonDiscount($discountId)
    {
        $sql = "
            SELECT
              sd.non_social_discount
            FROM
              seg_discount AS sd
            WHERE sd.discountid = '{$discountId}'
        ";

        $result = collect($this->pdo->select($sql))->recursive()->first();
        return $result['non_social_discount'];
    }

    /**
     * @param $discountId
     * @return mixed
     */
    public function getParentDiscount($discountId)
    {
        $sql = "
            SELECT
              sd.parentid
            FROM
              seg_discount AS sd
            WHERE sd.discountid = '{$discountId}'
        ";

        $result = collect($this->pdo->select($sql))->recursive()->first();
        return $result['parentid'];
    }
}
