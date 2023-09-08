<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//Library related to PhpOffice\PhpSpreadsheet
class Spsh_library {
    public $spsh_cols = [
        '',
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P',
        'Q','R','S','T','U','V','W','X','Y','Z',
        'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP',
        'AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
        'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP',
        'BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ',
        'CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM','CN','CO','CP',
        'CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ',
        'DA','DB','DC','DD','DE','DF','DG','DH','DI','DJ','DK','DL','DM','DN','DO','DP',
        'DQ','DR','DS','DT','DU','DV','DW','DX','DY','DZ',
    ];

    public function writeResultsetToSheet($resultset, $sheet, $start_col_idx, $start_row_idx)
    {
        $j = $start_row_idx;
        foreach ($resultset as $rec)
        {
            $i = $start_col_idx;
            foreach ($rec as $key => $value)
            {
                if($j==$start_row_idx)
                {
                    $cell_loc = $this->spsh_cols[$i].$j;
                    $sheet->getStyle($cell_loc.':'.$cell_loc)->getFont()->setBold(true);
                    $sheet->setCellValue($cell_loc, $key);

                    $cell_loc = $this->spsh_cols[$i].($j+1);
                    $sheet->setCellValue($cell_loc, $value);
                } else {
                    $cell_loc = $this->spsh_cols[$i].$j;
                    $sheet->setCellValue($cell_loc, $value);
                }
                $i++;
            }

            if($j==$start_row_idx) $j++;
            $j++;
        }
    }
}