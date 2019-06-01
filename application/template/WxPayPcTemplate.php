<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Language" content="zh-cn">
    <meta name="renderer" content="webkit"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>微信安全支付 - <?php echo $siteName ?></title>
    <link href="/static/css/wx/wechat_pay.css" rel="stylesheet" media="screen">
    <style>
        .loader,
        .loader:after {
            border-radius: 50%;
            width: 10em;
            height: 10em;
        }

        .loader {
            margin: 60px auto;
            font-size: 10px;
            position: relative;
            text-indent: -9999em;
            border-top: 0.4rem solid rgba(255, 255, 255, 0.2);
            border-right: 0.4rem solid rgba(255, 255, 255, 0.2);
            border-bottom: 0.4rem solid rgba(255, 255, 255, 0.2);
            border-left: 0.4rem solid #ffffff;
            -webkit-transform: translateZ(0);
            -ms-transform: translateZ(0);
            transform: translateZ(0);
            -webkit-animation: load8 1.1s infinite linear;
            animation: load8 1.1s infinite linear;
        }

        @-webkit-keyframes load8 {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }

        @keyframes load8 {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
<div class="body">
    <h1 class="mod-title">
        <span class="ico-wechat"></span><span class="text">微信支付</span>
    </h1>
    <div class="mod-ct">
        <div class="order">
        </div>
        <div class="amount">￥<?php echo $money; ?></div>
        <div class="qr-image" id="qrcode" style="display: none;">
        </div>
        <div class="qr-image" id="wait-qrcode" style="display: block;">
            <div style="text-align: center;width: 230px;height: 230px;margin: 0 auto;background-color: rgba(6,6,8,0.5);">
                <div class="loader" style="position: relative;top: 46px;"></div>
            </div>
        </div>
        <div class="detail" id="orderDetail">
            <dl class="detail-ct" style="display: none;">
                <dt>商家</dt>
                <dd id="storeName"><?php echo $siteName ?></dd>
                <dt>购买物品</dt>
                <dd id="productName"><?php echo $productName ?></dd>
                <dt>商户订单号</dt>
                <dd id="billId"><?php echo $tradeNo; ?></dd>
                <dt>创建时间</dt>
                <dd id="createTime"><?php echo $addTime; ?></dd>
            </dl>
            <a href="javascript:void(0)" class="arrow"><i class="ico-arrow"></i></a>
        </div>
        <div class="tip">
            <span class="dec dec-left"></span>
            <span class="dec dec-right"></span>
            <div class="ico-scan"></div>
            <div class="tip-text">
                <p>请耐心等候二维码生成</p>
                <p>扫描二维码完成支付</p>
            </div>
        </div>
        <div class="tip-text">
        </div>
    </div>
    <div class="foot">
        <div class="inner">
            <p>请耐心等候二维码生成</p>
            <p>在微信扫一扫中选择“相册”即可</p>
        </div>
    </div>
</div>
<script src="/static/js/qq/qrcode.min.js"></script>
<script src="/static/js/qq/qcloud_util.js"></script>
<script src="/static/js/layer/layer.js"></script>
<script>
    // 订单详情
    var data = ['<?php echo $time;?>', '<?php echo $orderID; ?>', '<?php echo $sign; ?>'];
    $('#orderDetail .arrow').click(function (event) {
        if ($('#orderDetail').hasClass('detail-open')) {
            $('#orderDetail .detail-ct').slideUp(500, function () {
                $('#orderDetail').removeClass('detail-open');
            });
        } else {
            $('#orderDetail .detail-ct').slideDown(500, function () {
                $('#orderDetail').addClass('detail-open');
            });
        }
    });
    //
    //// 检查是否支付完成
    function getOrderStatus() {
        $.ajax({
            type: 'get',
            dataType: 'json',
            url: '<?php echo url('/Pay/Hk/OrderStatus', '', false, true); ?>',
            timeout: 10000, //ajax请求超时时间10s
            data: {
                type: 1,
                tradeNo: data[1]
            },
            success: function (data) {
                //从服务器得到数据，显示数据并继续查询
                if (data['status'] === 1) {
                    layer.msg('支付成功，正在跳转中...', {icon: 16, shade: 0.01, time: 15000});
                    setTimeout(window.location.href = data['url'], 1000);
                } else if (data['status'] === -2) {
                    layer.open({
                        title: '订单已经超时'
                        , content: '切勿再进行扫码付款，请返回请求地址再次发起支付请求'
                    });
                    $('#wait-qrcode').show();
                    $('#qrcode').hide();
                } else {
                    setTimeout('getOrderStatus()', 4000);
                }
            },
            //Ajax请求超时，继续查询
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus === 'timeout') {
                    setTimeout('getOrderStatus()', 1000);
                } else { //异常
                    setTimeout('getOrderStatus()', 4000);
                }
            }
        });
    }

    //
    function getQrCode() {
        $.ajax({
            type: 'get',
            dataType: 'json',
            url: '<?php echo url('/Pay/Hk/QrCode', '', false, true); ?>',
            timeout: 10000, //ajax请求超时时间10s
            data: {
                time: data[0],
                orderID: data[1],
                sign: data[2]
            },
            success: function (data) {
                //从服务器得到数据，显示数据并继续查询
                if (data['status'] === 1) {
                    layer.msg('获取二维码成功，正在为您生成二维码', {icon: 16, shade: 0.01, time: 1000});
                    var qrcode = new QRCode('qrcode', {
                        text: data['qrCode'],
                        width: 230,
                        height: 230,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    $('#wait-qrcode').hide();
                    $('#qrcode').show();
                    getOrderStatus();
                } else if (data['status'] === -2) {
                    layer.open({
                        title: '订单已经超时'
                        , content: '切勿再进行扫码付款，请返回请求地址再次发起支付请求'
                    });
                    $('#wait-qrcode').show();
                    $('#qrcode').hide();
                } else {
                    setTimeout('getQrCode()', 2000);
                }
            },
            //Ajax请求超时，继续查询
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus === 'timeout') {
                    setTimeout('getQrCode()', 1000);
                } else { //异常
                    setTimeout('getQrCode()', 2000);
                }
            }
        });
    }

    window.onload = getQrCode;
</script>
</body>
</html>