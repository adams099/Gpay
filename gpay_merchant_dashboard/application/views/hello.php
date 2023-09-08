<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, max-age=0, must-revalidate">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    Hello <?=$first_name?>&nbsp;<?=$last_name?>.<br /><br />
    <?php
        echo $this->config->item('login_url').'<br />';

        // foreach ($master_std as $rec)
        // {
        //     echo $rec->code.'&nbsp;|&nbsp;'.$rec->dsc.'&nbsp;|&nbsp;'.$rec->note.'<br />';
        // }
        foreach ($master_std as $rec)
        {
            $tmp = '';
            foreach ($rec as $key => $value)
            {
                $tmp .= $value.'&nbsp;|&nbsp;';
            }
            echo $tmp.'<br />';
        }
    ?>
</body>
</html>