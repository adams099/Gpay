NOTE:
Semua url access dimulai dari controller.

misal:
http://localhost:10001/admin/reversals/index
berarti yang dibaca adalah folder controller/admin/reversals.php dengan fungsi bernama index.

contoh 2:
http://localhost:10001/admin/reversals/reversal_detail/2359
berarti yang dibaca adalah folder controller/admin/reversals.php dengan fungsi bernama reversal_detail dengan parameter 2359.

file untuk template:
controller: application\controllers\admin\Reversals.php
model : application\models\Reversal_model.php
views : application\views\admin\reversals
sidebar menu untuk menambah list menu : application\views\template.php

file config jika diperlukan :

config base_url : application\config\config.php
config database conn : application\config\database.php
config lain-lain : application\config\gpay.php (untuk case reversal, jumlah delay maximal hari transaksi boleh direversal dari tanggal transaksi tsb, yaitu $config['reversal_max_allowed_days'])
