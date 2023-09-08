<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, max-age=0, must-revalidate">
    <meta http-equiv="Expires" content="0">
    <title><?= (isset($title) ? $title : 'Merchant Aggregator Dashboard'); ?></title>
	
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= base_url('file/css/bootstrap.min.css'); ?>" type="text/css">

    <!-- CSS Addtional Plugins (only for this page) -->
    <?php foreach ((is_array($css_plugin) ? $css_plugin : array()) as $css): ?>
        <?= $css; ?>
    <?php endforeach; ?>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('file/css/theme.css'); ?>" type="text/css">
    <link rel="stylesheet" href="<?= base_url('file/css/jquery.datetimepicker.css'); ?>" type="text/css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.css">

    <link rel="stylesheet" href="<?= base_url('file/css/custom.css'); ?>" type="text/css">
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
    
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js"></script>
    
    <!-- Jquery UI CSS -->
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker.min.css">

    
</head>

<body>
    <?php $action = $this->session->userdata('action'); ?>
        <div class="row" id="body-row">
            <div id="sidebar-container" class="sidebar-expanded d-none d-md-block">
                <ul class="list-group">
                    <li class="list-group-item sidebar-separator-title text-muted d-flex align-items-center menu-collapsed">
                        <a class="navbar-brand" href="<?= site_url('home/index'); ?>">
                            <img src="<?= site_url('/file/img/logo-me-dash.png'); ?>" width="100%" class="d-inline-block align-top" alt="">
                        </a>
                    </li>                
                    <a href="#submenu1" data-toggle="collapse" aria-expanded="false" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="ico-dash mr-3"></span>
                            <span class="menu-collapsed">Dashboard</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                    <div id='submenu1' class="collapse sidebar-submenu">
                    <?php if(array_key_exists('dashboard',$action) && in_array('dashboard_search',$action['dashboard'])){ ?>
                        <a href="<?= site_url('dashboard/index'); ?>" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">Merchant Aggregator</span>
                        </a>
                    <?php } ?>
                    <?php if(array_key_exists('dashboard_va',$action) && in_array('dashboard_va_search',$action['dashboard_va'])){ ?>
                        <a href="<?= site_url('va_dashboard/index'); ?>" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">Virtual Account</span>
                        </a>
                    <?php } ?>
                    <?php if(array_key_exists('dashboard_ppob',$action) && in_array('dashboard_ppob_search',$action['dashboard_ppob'])){ ?>
                        <a href="#" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">PPOB</span>
                        </a>
                    <?php } ?>
                    </div>
                    <a href="#submenu2" data-toggle="collapse" aria-expanded="false" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="ico-trans-hist mr-3"></span>
                            <span class="menu-collapsed">Transaction History</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                    <div id='submenu2' class="collapse sidebar-submenu">
                    <?php if(array_key_exists('qris_online',$action) && in_array('qris_online_search',$action['qris_online'])){ ?>
                        <a href="<?= site_url('qris_online/index'); ?>" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">QRIS Online</span>
                        </a>
                    <?php } ?>
                    <?php if(array_key_exists('transaction_history',$action) && in_array('trx_hist_search',$action['transaction_history'])){ ?>
                        <a href="<?= site_url('transaction_history/index'); ?>" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">Merchant Aggregator</span>
                        </a>
                    <?php } ?>
                    <?php if(array_key_exists('mandiri_va_transaction_history',$action) && in_array('mandiri_va_transaction_history_search',$action['mandiri_va_transaction_history'])){ ?>
                        <a href="<?= site_url('mandiri_va_transaction_history/index'); ?>" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">Virtual Account Trans</span>
                        </a>
                    <?php } ?>
                    <?php if(array_key_exists('transaction_history_va',$action) && in_array('trx_hist_va_search',$action['transaction_history_va'])){ ?>
                        <a href="<?= site_url('va_transaction_history/index'); ?>" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">BCA Virtual Account</span>
                        </a>
                    <?php } ?>
                    <?php if(array_key_exists('transaction_history_ppob',$action) && in_array('trx_hist_ppob_search',$action['transaction_history_ppob'])){ ?>
                        <a href="<?= site_url('ppob_transaction_history/index'); ?>" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">PPOB</span>
                        </a>
                    <?php } ?>
                    </div>        
                    <?php if(array_key_exists('reports',$action) && in_array('reports_search',$action['reports'])){ ?>    
                    <a href="#submenu3" data-toggle="collapse" aria-expanded="false" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="ico-report mr-3"></span>
                            <span class="menu-collapsed">Reports</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                    
                    <div id='submenu3' class="collapse sidebar-submenu">
                        <a href="#" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">Merchant Aggregator</span>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">Virtual Account</span>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action text-blue">
                            <span class="menu-collapsed">PPOB</span>
                        </a>
                    </div>
                    <?php } ?>
                    <?php if(array_key_exists('va_unpaid',$action) && in_array('va_unpaid_search',$action['va_unpaid'])){ ?>
                    <a href="<?= site_url('va_transaction_unpaid/index'); ?>" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="ico-account mr-3"></span>
                            <span class="menu-collapsed">Unpaid</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                    <?php } ?>
                    <?php if(array_key_exists('account',$action) && in_array('account_search',$action['account'])){ ?>
                    <a href="<?= site_url('account/index'); ?>" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="ico-account mr-3"></span>
                            <span class="menu-collapsed">Account</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                    <?php } ?>
                    <?php if(array_key_exists('contact_us',$action) && in_array('contact_us_page',$action['contact_us'])){ ?>
                    <a href="<?= site_url('home'); ?>" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="ico-contact mr-2"></span>
                            <span class="menu-collapsed">Contact Us</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                    <?php } ?>
                    <a href="<?= site_url('home/logout'); ?>" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="ico-logout mr-2"></span>
                            <span class="menu-collapsed">Logout</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                </ul>
                <div class="bg-sidebar"></div>
            </div>
            <!-- End Sidebar -->
            <!-- MAIN -->
            <div class="col">
                <!-- CONTENT -->
                <?= $content; ?>
            </div>
            <!-- END MAIN -->
        </div>
        

<style>
    .datepicker {
      z-index: 1600 !important; /* has to be larger than 1050 */
    }
</style>
    <!-- Bootstrap JS -->
    <script src="<?= base_url('file/js/jquery-3.4.1.min.js'); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="<?= base_url('file/js/bootstrap.min.js'); ?>" ></script>

    <!-- Custom JS -->
    <script type="text/javascript" src="<?= base_url('file/js/jquery.datatable.min.js'); ?>" ></script>
    <script src="<?= base_url('file/js/config.js'); ?>" ></script>

    <!-- JS Addtional Plugins (only for this page) -->
    <?php foreach ((is_array($js_plugin) ? $js_plugin : array()) as $js): ?>
        <?= $js; ?>
    <?php endforeach; ?>

    <!-- Jquery UI JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

    <script>
    /*menu handler*/
    $(function(){
        function stripTrailingSlash(str) {
            if(str.substr(-1) == '/') {
                return str.substr(0, str.length - 1);
            }
            return str;
        }

        /*somehow pathname works on dev server, but not in local, please use href in local*/
        var url = window.location.pathname;    
        var activePage = stripTrailingSlash(url);
  
        $('#sidebar-container ul div a').each(function(){  
            var currentPage = stripTrailingSlash($(this).attr('href'));
        
            if (activePage == currentPage) {
                $(this).parent().addClass('show');  
                $(this).addClass('active');
                let current = document.querySelector('.show');
                let prev = current.previousElementSibling;
                prev.setAttribute("aria-expanded", true)
            } 
        });
    });
    </script>
    
</body>
</html>