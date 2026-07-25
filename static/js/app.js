/**
 * Created by TonyVN on 05/03/2016.
 */
var hotro = {
    utils: {}
};

$(document).ready(function() {
    $('.date-time-picker').datetimepicker({
        uiLibrary: 'bootstrap4',
        footer: true,
        format: 'dd/mm/yyyy HH:MM'
    });
    $('.date-picker').datepicker({
        uiLibrary: 'bootstrap4',
        footer: true,
        format: 'dd/mm/yyyy'
    });
    $('.time-picker').timepicker({
        uiLibrary: 'bootstrap4',
        footer: true,
        format: 'HH:MM'
    });
    var pagePopupModalId = $("#pagePopupModal #popup-id").val();
    if (pagePopupModalId) {
        $('#pagePopupModal').modal({
            backdrop: 'static',
            keyboard: false
        });
        $("#pagePopupModal #btn-ok-con").click(function() {
            popupAction(pagePopupModalId, 0);
        });
        $("#pagePopupModal #btn-cancel-con").click(function() {
            popupAction(pagePopupModalId, 1);
        });
        $("#pagePopupModal .close").click(function() {
            popupAction(pagePopupModalId, 1);
        });
    }

    var popupAction = function(pagePopupModalId, isCancel) {
        $.ajax({
            url: '/popup_action/',
            method: 'POST',
            data: {
                id: pagePopupModalId,
                is_cancel: isCancel,
                csrfmiddlewaretoken: $("#pagePopupModal #popup-token").val()
            },
            success: function(data) {
                if (data.status) {

                }
            }
        });
        $('#pagePopupModal').modal("hide");
    };

    var viewRequestPopupModalId = $("#viewRequestPopupModal #popup-id").val();
    if (viewRequestPopupModalId) {
        $('#viewRequestPopupModal').modal({
            backdrop: 'static',
            keyboard: false
        });
        $("#viewRequestPopupModal #btn-ok-con").click(function() {
            viewRequestPopupAction(viewRequestPopupModalId, 1);
        });
        $("#viewRequestPopupModal #btn-cancel-con").click(function() {
            viewRequestPopupAction(viewRequestPopupModalId, 0);
        });
        $("#viewRequestPopupModal .close").click(function() {
            viewRequestPopupAction(viewRequestPopupModalId, 1);
        });

        var begin = 10;
        $("#viewRequestPopupModal #btn-cancel-con").html('Không nhắc lại (' + begin + ')');
        setInterval(function() {
            begin -= 1;
            if (begin > 0) {
                $("#viewRequestPopupModal #btn-cancel-con").html('Không nhắc lại (' + begin + ')');
            } else {
                $("#viewRequestPopupModal #btn-cancel-con").html('Không nhắc lại');
                $("#viewRequestPopupModal #btn-cancel-con").removeAttr('disabled');
            }

        }, 1000);
    }

    var viewRequestPopupAction = function(pagePopupModalId, isCancel) {
        $.ajax({
            url: '/popup_action_request/',
            method: 'POST',
            data: {
                id: pagePopupModalId,
                is_cancel: isCancel,
                csrfmiddlewaretoken: $("#pagePopupModal #popup-token").val()
            },
            success: function(data) {
                if (data.status) {

                }
            }
        });
        $('#viewRequestPopupModal').modal("hide");
    };
});

hotro.utils.showLoadingPopup = function() {
    $("#loadingModal").modal({
        backdrop: 'static'
    });
};

hotro.utils.hideLoadingPopup = function() {
    $("#loadingModal").modal("hide");
};

hotro.utils.showPopupMessage = function(title, msg, isDelay) {
    if (title) {
        $("#feedbackModalLabel").html(title);
    }
    $("#feedbackModalContent").html(msg);
    if (isDelay) {
        setTimeout(function() {
            $("#feedbackModal").modal();
        }, 500);
    } else {
        $("#feedbackModal").modal();
    }
};

hotro.utils.getAnchor = function(defaultValue) {
    var currentURL = window.location.href;
    var currentURLBits = currentURL.split("#");
    if (currentURLBits.length > 1) {
        return currentURLBits[1];
    } else {
        return defaultValue;
    }
};

hotro.utils.setAnchor = function(anchorValue) {
    var currentURL = window.location.href;
    var currentURLBits = currentURL.split("#");
    window.location = currentURLBits[0] + "#" + anchorValue;
};

hotro.HomeHandler = function() {
    this.init = function() {
        $(".group").hide();
        $('.chucnang a').click(function(e) {
            var $this = $(this);
            if ($this.hasClass('not-tab')) {
                return;
            }

            e.preventDefault();
            $('.func').removeClass('func-active');
            $(this).addClass('func-active');
            var showIt = $this.attr('href');
            $(".group").hide();
            $(showIt).show();
            hotro.utils.setAnchor(showIt.split("-")[1]);
        });
        $('.listgame').hide();

        $(".banner-slider").slick({
            dots: false,
            infinite: true,
            speed: 500,
            slidesToShow: 1,
            slidesToScroll: 1,
            // autoplay: true,
            responsive: [{
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: false
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        dots: true
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        dots: true
                    }
                }
            ]
        });

        $('.trochoi').click(function() {
            var mh = screen.width;
            $(this).addClass('focus');
            $('.taikhoan').removeClass('focus');
            if (mh > 414) {
                //                 $('#htgroup').addClass('col-md-8 col-md-push-2');
                $('.content .group .link').css('padding', '20px 14px');
            } else {

            }
            if (hotro.utils.getAnchor('game') != 'game') {
                hotro.utils.setAnchor('game');
            }
            $('.listgame').show();
            $(".game-slider").slick({
                dots: false,
                infinite: true,
                speed: 500,
                slidesToShow: 5,
                slidesToScroll: 3,
                // autoplay: true,
                responsive: [{
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 5,
                            slidesToScroll: 3,
                            infinite: true,
                            dots: false
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                            dots: true
                        }
                    },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                            dots: true
                        }
                    }
                ]
            });
        });
        $('.gameimg').hover(function() {
            $(this).addClass('hover');
        });
        var imgitem = $(".item a img");
        imgitem.mouseenter(function() {
            imgitem.css('opacity', '0.2');
            imgitem.css('transition', 'all 0.5s ease');
            $(this).css('opacity', '1');
            $(this).css('transition', 'all 0.5s ease');
        });
        imgitem.mouseleave(function() {
            $('.item a img').css('opacity', '1');
            $(this).css('transition', 'all 0.5s ease');
        });

        var activeSection = hotro.utils.getAnchor('game');
        var isGameListActive = false;
        if (activeSection == 'game') {
            activeSection = 'hotro';
            isGameListActive = true;
        }
        $("#menu-group-" + activeSection).addClass('func-active');
        $("#group-" + activeSection).show();
        if (isGameListActive) {
            $('.trochoi').click();
        }

    };



};

hotro.FAQDetailHandler = function() {
    this.checkLiveSupportInterval = 5000;
    this.init = function(is_user_login) {
        this.showLiveSupport();
        var $this = this;

        setInterval(function() {
            fAQDetailHandler.checkLiveSupport();
        }, fAQDetailHandler.checkLiveSupportInterval);

        $(".faq-point").click(function() {
            hotro.utils.showLoadingPopup();
            var point = $(this).data('point');
            $.ajax({
                url: '/rate_faq/',
                method: 'POST',
                data: {
                    point: point,
                    faq_id: $("#_faq-id").val()
                },
                success: function(data) {
                    hotro.utils.hideLoadingPopup();
                    if (data.status) {
                        //hotro.utils.showPopupMessage("THÔNG BÁO", "Cảm ơn bạn đã cho ý kiến đánh giá", true);
                        //$("#khaosat_box").addClass("hide");
                        //$("#gui_y_kien_box").removeClass("hide");
                    } else {
                        //hotro.utils.showPopupMessage("LỖI", data.message, true);
                    }
                    $("#khaosat_box").addClass("hide");
                    $("#gui_y_kien_box").removeClass("hide");
                }
            });
        });

        $("#feedback-faq").click(function() {
            var feedbackContent = $("#_feedback-content").val().trim();
            if (feedbackContent === '') {
                hotro.utils.showPopupMessage("Lỗi", "Xin vui lòng nhập ý kiến của bạn!");
            } else {
                hotro.utils.showLoadingPopup();
                $.ajax({
                    url: '/feedback_faq/',
                    method: 'POST',
                    data: {
                        content: feedbackContent,
                        faq_id: $("#_faq-id").val()
                    },
                    success: function(data) {
                        hotro.utils.hideLoadingPopup();
                        /*
                        if (data.status){
                            hotro.utils.showPopupMessage("THÔNG BÁO", "Cảm ơn bạn đã đóng góp ý kiến phản hồi", true);
                            $(".guiykien").addClass("hide");    
                        }else{
                            hotro.utils.showPopupMessage("LỖI", data.message, true);
                        }
                        */
                        $("#gui_y_kien_box").addClass("hide");
                        $("#feeback_result_box").removeClass("hide");
                    }
                });
            }
        });

        $("#feedback-faq-cancel").click(function() {
            $("#gui_y_kien_box").addClass("hide");
            $("#feeback_result_box").removeClass("hide");
            return false;
        });

        $("._request-phonecall").click(function() {
            if ($(".hotro-contact").hasClass("hide")) {
                var sectionContentLength = $(".kenhhotro > .container > .row > .list-hh > div").length;
                if (sectionContentLength < 4) {
                    var call_with = 0;
                    if (sectionContentLength == 2) {
                        call_with = 17;
                    } else if (sectionContentLength == 3) {
                        call_with = 50;
                    }
                    $('.muiten').css('right', 'initial');
                    $('.muiten').css('left', call_with + '%');
                }
                $(".hotro-contact").removeClass("hide");
                window.scrollTo(0, $(window).scrollTop() + $(window).height());
            } else {
                $(".hotro-contact").addClass("hide");
            }
        });
    };

    this.showLiveSupport = function() {
        var currentHours = currentDateTime.getHours();
        var currentMinutes = currentDateTime.getMinutes();
        if (currentHours < 6 || (currentHours == 6 && currentMinutes <= 30)) {
            $("#request_chat_box").css('visibility', 'hidden');
            $("#request_phone_box").css('visibility', 'hidden');
            $("#kenhhotro-title-live").hide();
            $("#kenhhotro-title-off").show();
        } else {
            $("#request_chat_box").css('visibility', 'visible');
            $("#request_phone_box").css('visibility', 'visible');
            $("#kenhhotro-title-live").show();
            $("#kenhhotro-title-off").hide();
        }
    };

    this.checkLiveSupport = function() {
        currentDateTime = new Date(currentDateTime.getTime() + this.checkLiveSupportInterval);
        this.showLiveSupport();
    };
};

hotro.ChatHandler = function() {
    this.init = function(is_user_login) {
        var $this = this;

        $('.request_chat_box').click(function() {
            if (is_user_login) {
                if ($(this).hasClass('shown')) {
                    $("#chat-area a").trigger("click");
                } else {
                    $('.chat-init').hide();
                    $('.chat-area-loading').show();
                    $this.loadChat();
                }
            } else {
                window.location.href = '/yeu-cau-dang-nhap/';
            }
        });
    };

    this.loadChat = function() {
        Comm100API = new Object;
        Comm100API.chat_buttons = Comm100API.chat_buttons || [];
        var comm100_chatButton = new Object;
        comm100_chatButton.code_plan = 7;
        comm100_chatButton.div_id = 'chat-area';
        Comm100API.chat_buttons.push(comm100_chatButton);
        Comm100API.site_id = 100010000;
        Comm100API.main_code_plan = 7;

        var comm100_lc = document.createElement('script');
        comm100_lc.type = 'text/javascript';
        comm100_lc.async = true;
        comm100_lc.src = 'https://livechat.ved.com.vn/chatserver/livechat.ashx?siteId=' + Comm100API.site_id;
        var comm100_s = document.getElementsByTagName('script')[0];
        comm100_s.parentNode.insertBefore(comm100_lc, comm100_s);

        var intervalId = setInterval(showChatPopup, 1000);

        function showChatPopup() {
            if (Comm100API.loaded && $('#chat-area a').length) {
                $(".request_chat_box").addClass("shown");
                $('.chat-init').show();
                $('.chat-area-loading').hide();
                $("#chat-area a").trigger("click");
                clearIntervalTurnOnChatPopup();
            }
        }

        function clearIntervalTurnOnChatPopup() {
            clearInterval(intervalId);
        }

    }
};

hotro.CategoryListHandler = function() {
    this.issueList = [];
    this.level1 = 0;
    this.level2 = 0;
    this.level3 = 0;
    this.totalIssue = 0;
    this.current_step = 1;
    this.selectedTabMenu = false;
    this.init = function(issueList, level1, level2, level3, current_step) {
        this.issueList = issueList;
        this.level1 = level1;
        this.level2 = level2;
        this.level3 = level3;
        this.current_step = current_step;
        var me = this;
        this.totalIssue = this.issueList.length;
        this.fillLevelData();

        //$('.step2').click(false);
        //$('.step3').click(false);
        this.bindChangeIssueEvent();
        $(".wizardbar-item").on("click", function(e) {
            e.preventDefault();
            var step = $(this).data('step');
            if (step < me.current_step) {
                $(this).siblings(".current").removeClass("current");
                $(this).addClass("current");
                me.displayArea(step);
            }
        });

        $("#search-step1-form").submit(function() {
            if ($('#search_keyword').val().length > 1) {
                var sub_issue_ids = [];
                if (me.level2) {
                    sub_issue_ids.push(me.level2);
                }
                if (me.level3) {
                    sub_issue_ids.push(me.level3);
                }
                $("#sub_issue_id").val(sub_issue_ids.join('-'));
                return true;
            } else {
                hotro.utils.showPopupMessage("Lỗi", "Xin vui lòng nhập ít nhất 2 kí tự để tìm kiếm!");
                return false;
            }
        });

        $("#search-step1").click(function() {
            $("#search-step1-form").submit();
        });
    };

    this.bindChangeIssueEvent = function() {
        var me = this;
        $("#main-issue").unbind("change").change(function() {
            //me.level2 = this.value;
            var bits = window.location.href.split('/');
            window.location = [bits[0], bits[1], bits[2], bits[3], bits[4]].join('/') + '?sub_issue_id=' + this.value;
            //me.changeLevel2(false);
        });
        $("#issue").unbind("change").change(function() {
            var bits = window.location.href.split('/');
            var sub_issue_id = $("#main-issue").val() + '-' + this.value;
            window.location = [bits[0], bits[1], bits[2], bits[3], bits[4]].join('/') + '?sub_issue_id=' + sub_issue_id;
        });
    };

    this.fillLevelData = function() {
        var idx = 0;
        var issuesLevel3 = false;
        var $mainIssue = $("#main-issue");
        for (; idx < this.totalIssue; idx++) {
            var issue = this.issueList[idx];
            $('<option value="' + issue.id + '"' + (issue.id == this.level2 ? ' selected' : '') + '>' + issue.name + '</option>').appendTo($mainIssue);
            if (issue.id == this.level2) {
                issuesLevel3 = issue.children;
            }
        }
        if (issuesLevel3 !== false) {
            this.changeLevel2(issuesLevel3);
        }
        if (this.current_step == 2) {
            this.displayArea(this.current_step);
        }
    };

    this.changeLevel2 = function(issuesLevel3) {
        var $issue = $("#issue");
        if (this.level2 === '') {
            if (this.current_step > 1) {
                $issue.parent().addClass('hide');
                $(".wizardbar-item").removeClass("current");
                $("#detail-1").addClass("current");
                $("#detail-2").css("cursor", "default");
                this.current_step = 1;
                this.displayArea(this.current_step);
            }
            this.level3 = "";
        } else {
            var idx = 0;
            if (issuesLevel3 === false) {
                for (idx = 0; idx < this.totalIssue; idx++) {
                    var issue = this.issueList[idx];
                    if (issue.id == this.level2) {
                        issuesLevel3 = issue.children;
                    }
                }
            }
            if (issuesLevel3.length === 0) {
                $issue.html("<option value=''\>Hiện chưa có vấn đề phù hợp");
            } else {
                $issue.html("<option value=''\>-- Chọn một vấn đề --");
                for (idx = 0; idx < issuesLevel3.length; idx++) {
                    var sub_issue = issuesLevel3[idx];
                    $('<option value="' + sub_issue.id + '"' + (sub_issue.id == this.level3 ? ' selected' : '') + '>' + sub_issue.name + '</option>').appendTo($issue);
                }
            }
            $issue.parent().removeClass('hide');
        }
    };

    this.displayArea = function(step) {
        if (this.selectedTabMenu !== false && step == this.selectedTabMenu)
            return;
        step = parseInt(step);
        switch (step) {
            case 1:
                $("#step2").removeClass('active');
                $("#step2").removeClass('in');
                $("#step3").removeClass('active');
                $("#step3").removeClass('in');
                $("#step1").addClass('active in');
                if (this.selectedTabMenu !== false) {
                    $("#detail-2").css("cursor", "default");
                    $("#main-issue").val("").trigger('change');
                }
                break;
            case 2:
                $("#step1").removeClass('active');
                $("#step1").removeClass('in');
                $("#step3").removeClass('active');
                $("#step3").removeClass('in');

                $("#step2").addClass('active in');
                break;
            case 3:
                $("#step1").removeClass('active');
                $("#step1").removeClass('in');
                $("#step2").removeClass('active');
                $("#step2").removeClass('in');

                $("#step3").addClass('active in');
                break;
            default:
                return false;
        }
        this.selectedTabMenu = step;
    };
};

hotro.CustomerHandler = function() {
    this.form_fields = ['gender', 'issue_date', 'issue_place', 'birthday', 'phone', 'email', 'address', 'province_id'];
    this.isEditable = 0;
    this.isFromV1 = false;
    this.allowed_fields = [];
    this.init = function(isEditable, isFromV1, allowed_fields) {
        this.isEditable = isEditable;
        this.isFromV1 = isFromV1;
        this.allowed_fields = allowed_fields;
        $.fn.editable.defaults.mode = 'inline';
        var me = this;
        if ($.inArray('province_id', this.allowed_fields)) {
            var selectedProvince = $("#province_id_").val();
            var provinceText = " - ";
            if (selectedProvince != "" && selectedProvince != "0") {
                provinceText += $("#province_id_ option:selected").text();
            }
            $("#address_dump_").html($("#address_dump_").html() + provinceText);
        }
        if ($.inArray('address', this.allowed_fields)) {
            var selectedIssuePlace = $("#issue_place_").val();
            if (selectedIssuePlace != "" && selectedIssuePlace != "0") {
                $("#issue_place_dump_").html($("#issue_place_ option:selected").text());
            }
        }

        if (this.allowed_fields.length > 0) {
            $('.date-picker').datepicker({
                autoclose: true,
                todayHighlight: true,
                format: 'dd/mm/yyyy',
            });
            this.showInputFields(true);
            $(".submituser").show();
        }
        $('#enable').click(function() {
            me.showInputFields(true);
            $(".submituser").show();
            $(this).hide();
            //$('#userinfo .editable').editable();
        });

        $('#cancel_update_user').click(function() {
            me.showInputFields(false);
            $(".submituser").hide();
            $("#enable").show();
            $(".form_error_field").text("");
        });
        $('#edit_info_user_v1_notify').click(function(e) {
            alert("Bạn chỉ được cập nhật thông tin cá nhân một lần và không thể tự thay đổi vì lý do bảo mật.");
            $(this).hide();
            $('#update_user').show();
        });
        $('#update_user').click(function(e) {
            var issue_date = $("#issue_date_");
            if ($.inArray('issue_date', me.allowed_fields) !== -1) {
                if (issue_date.val() === '') {
                    alert("Bạn chưa nhập thông tin ngày cấp.");
                    issue_date.focus();
                    return;
                }
                var issue_date_obj = moment(issue_date.val(), "DD/MM/YYYY").toDate();
                var today_date = new Date().setHours(0, 0, 0, 0);
                if (issue_date_obj > today_date) {
                    alert("Ngày cấp phải nhỏ hơn hoặc bằng ngày hiện tại.");
                    issue_date.focus();
                    return;
                }
            }

            if ($.inArray('issue_place', me.allowed_fields) !== -1) {
                var issue_place = $("#issue_place_");
                if (issue_place.val() === '0') {
                    alert("Bạn chưa chọn thông tin nơi cấp.");
                    issue_place.focus();
                    return;
                }
            }

            var birthday = $("#birthday_");
            if ($.inArray('birthday', me.allowed_fields) !== -1) {
                if (birthday.val() === '') {
                    alert("Bạn chưa nhập thông tin ngày sinh.");
                    birthday.focus();
                    return;
                }
            }

            if ($.inArray('issue_date', me.allowed_fields) !== -1 &&
                $.inArray('birthday', me.allowed_fields) !== -1) {
                var birthday_obj = moment(birthday.val(), "DD/MM/YYYY").add(14, 'years').toDate();
                if (issue_date_obj < birthday_obj) {
                    alert("Phải ít nhất 14 tuổi mới được cấp CMT.");
                    birthday.focus();
                    return;
                }
            }

            if ($.inArray('address', me.allowed_fields) !== -1) {
                var address = $("#address_");
                if (address.val() === '') {
                    alert("Bạn chưa nhập thông tin địa chỉ.");
                    address.focus();
                    return;
                }
            }

            if ($.inArray('province_id', me.allowed_fields) !== -1) {
                var province_id = $("#province_id_");
                if (province_id.val() === '0') {
                    alert("Bạn chưa chọn thông tin Tỉnh/Thành Phố.");
                    province_id.focus();
                    return;
                }
            }

            var answer = confirm("Bạn chắc chắn muốn cập nhật thông tin liên hệ?");
            if (answer == true) {
                e.preventDefault();
                var postData = {};
                for (var idx in me.allowed_fields) {
                    var form_field = me.allowed_fields[idx];
                    var $form_field = $("#" + form_field + "_");
                    var form_field_val = $form_field.val();
                    if (form_field_val === '' && $form_field.data('value')) {
                        form_field_val = $form_field.data('value');
                    }
                    postData[form_field] = form_field_val;
                }
                $.ajax({
                    url: '/nguoi-dung/cap_nhat',
                    data: postData,
                    type: 'POST',
                    dataType: 'JSON',
                    success: function(res) {
                        if (res.status) {
                            alert('Cập nhật thành công');
                            location.reload();
                        } else {
                            $(".form_error_field").text("");
                            $.each(res.errors, function(field, error_message) {
                                $("#" + field + "_error").text(error_message);
                            });
                        }
                    }
                });
            }

        });
    };

    this.showInputFields = function(status) {
        var idx;
        for (idx in this.allowed_fields) {
            var form_field = this.allowed_fields[idx];
            if (status) {
                var $form_field = $("#" + form_field + "_");
                var $form_field_dump = $("#" + form_field + "_dump_");
                if (form_field != 'gender' && form_field != 'province_id') {
                    var form_field_val = $form_field.data('value');
                    if (form_field_val !== '') {
                        $form_field.attr("placeholder", $form_field_dump.val());
                    }
                }
                $form_field.show();
                $form_field_dump.hide();
                //                 $("#" + form_field + "_").show();
            } else {
                $("#" + form_field + "_").hide();
                $("#" + form_field + "_dump_").show();
            }
        }
    };
};

hotro.ViewRequestHandler = function() {

    this.pendingRequestIds = [];
    this.delayCheckRequestStatus = 0;
    this.totalItem = 0;
    this.ITEM_PER_PAGE = 10;
    this.currentPageIdx = 0;
    this.totalPage = 0;
    this.filterItemIndexs = [];
    this.$pagingContainer = $("#pagination-container");
    this.$dropdownStatusLabel = $("#dropdownStatusLabel");

    this.init = function() {
        var me = this;
        var allTitle = $('.ques');
        $($('.faqil')[0]).addClass('active');
        this.totalItem = $('.answer-list > .answer-item').length;
        if (this.totalItem === 0) {
            $("#no-data-box").show();
        } else {
            for (var i = 0; i < this.totalItem; i++)
                this.filterItemIndexs.push(i);
            this.renderPagingNav();
        }
        allTitle.click(function() {
            var $this = $(this),
                content = $this.next('.ques-content').find('.ques-content-tgg');
            var $root_obj = $($this.parent().parent().parent());
            if (!$this.hasClass('dadoc')) {
                var request_id = $root_obj.data('requestid');
                var request_status = $root_obj.data('status');
                $('.faqil').each(function() {
                    var $faqil = $(this);
                    if ($faqil.data('status') == request_status) {
                        var $notiSpan = $faqil.find('.noti');
                        var notiNum = $notiSpan.data('num') - 1;
                        $notiSpan.data('num', notiNum);
                        if (notiNum < 1) {
                            $notiSpan.hide();
                        } else {
                            $notiSpan.text(notiNum);
                        }
                    }
                });
                $this.addClass('dadoc');
                $.ajax({
                    url: '/request_read/' + request_id,
                    data: {},
                    type: 'POST',
                    dataType: 'JSON',
                    success: function(res) {}
                });
            }
            //console.log(request_id);
            if ($(this).hasClass('active')) {
                content.slideUp();
                $this.removeClass('active');
                $root_obj.removeClass('current');
            } else {
                $('.ques-content-tgg').hide();
                allTitle.removeClass('active');
                $('.answer-list > .answer-item').removeClass("current");
                content.slideDown(function() {
                    $('html, body').animate({
                        scrollTop: $this.offset().top
                    }, 500);
                });
                $this.addClass('active');
                $root_obj.addClass('current');
            }
        });

        $(".feedback-form .rating-item").click(function() {
            var $this = $(this);
            var request_id = $this.data("requestid");
            var rate_num = $this.data("rate");
            $("#hidden-rating-" + request_id).val(rate_num);
            var $allRateItem = $this.parent().find('.rating-item');
            $allRateItem.removeClass("rated").addClass("not-rated");
            for (var i = 0; i < $allRateItem.length; i++) {
                var $rateItem = $($allRateItem[i]);
                if ($rateItem.data("rate") <= rate_num) {
                    $rateItem.removeClass("not-rated").addClass("rated");
                }
            }
        });

        $(".feedback-form").submit(function() {
            var $this = $(this);
            var rateNum = $this.find('[name=rate]').val();
            if (rateNum == '') {
                alert("Xin vui lòng đánh giá số sao!");
                return false;
            } else {
                return true;
            }
        });

        $(".faqil").click(function() {
            var $this = $(this);
            if ($this.hasClass('active')) {
                return false;
            }
            $(".faqil").removeClass('active');
            $this.addClass('active');
            var chooseStatus = $this.data("status");
            var itemCount = 0;
            var idx = 0;
            me.filterItemIndexs = [];
            $('.answer-list > .answer-item').each(function() {
                var $answer_this = $(this);
                if (chooseStatus == 0 || chooseStatus == $answer_this.data('status')) {
                    me.filterItemIndexs.push(idx);
                    //$answer_this.show();
                    itemCount++;
                } else {
                    //$answer_this.hide();
                }
                idx++;
            });
            me.totalItem = itemCount;
            if (itemCount > 0) {
                $("#no-data-box").hide();
            } else {
                $("#no-data-box").show();
            }
            me.renderPagingNav();
            if (me.$dropdownStatusLabel.length > 0) {
                var labelText = $this.find('a').html();
                labelText = labelText.split('<span')[0];
                me.$dropdownStatusLabel.text(labelText);
                $('.dropdown-menu').dropdown('toggle');

            }
            return false;
        });
        var currentURL = window.location.href;
        var currentURLBits = currentURL.split("?id=");
        if (currentURLBits.length > 1) {
            var selectedRequestId = currentURLBits[1];
            $(".answer-list > .answer-item").each(function() {
                var $rateItem = $(this);
                if ($rateItem.data('requestid') == selectedRequestId) {
                    var contentBoxContainer = $rateItem.find('.ques');
                    var contentBox = $rateItem.find('.ques-content-tgg');
                    contentBox.slideDown(function() {
                        $('html, body').animate({
                            scrollTop: contentBoxContainer.offset().top
                        }, 500);
                    });
                    contentBoxContainer.addClass('active');
                }
            });
        }
        var $pendingTickets = $(".pending-ticket");
        if ($pendingTickets.length > 0) {
            $pendingTickets.each(function() {
                var $pendingTicket = $(this);
                me.pendingRequestIds.push(parseInt($pendingTicket.data("requestid")));
            });
            setTimeout(function() {
                viewRequestHandler.updateTicketIdStatus();
            }, 10000);
        }
    };

    this.updateTicketIdStatus = function() {
        var me = this;
        $.ajax({
            url: '/request_get_ticket_ids',
            data: {
                ids: this.pendingRequestIds.join(',')
            },
            type: 'POST',
            dataType: 'JSON',
            success: function(res) {
                var removeIds = [];
                for (var i = 0; i < me.pendingRequestIds.length; i++) {
                    var pendingRequestId = me.pendingRequestIds[i];
                    try {
                        if (res[pendingRequestId] && res[pendingRequestId] === -1) {
                            $("#pending-ticket-" + pendingRequestId).html("<span style=\"color:#e74c3c\"><b>Yêu cầu bị trùng lặp</b></span>");
                            removeIds.push(pendingRequestId);
                        } else if (res[pendingRequestId]) {
                            $("#pending-ticket-" + pendingRequestId).text(res[pendingRequestId]);
                            removeIds.push(pendingRequestId);
                        }
                    } catch (err) {}
                }
                var removeLength = removeIds.length;
                if (removeLength > 0) {
                    for (i = 0; i < removeLength; i++) {
                        for (var j = me.pendingRequestIds.length; j >= 0; j--) {
                            if (me.pendingRequestIds[j] === removeIds[i]) {
                                me.pendingRequestIds.splice(j, 1);
                                break;
                            }
                        }
                    }
                }
                if (me.pendingRequestIds.length > 0) {
                    me.delayCheckRequestStatus += 10000;
                    setTimeout(function() {
                        viewRequestHandler.updateTicketIdStatus();
                    }, 10000 + me.delayCheckRequestStatus);
                }
            }
        });
    };

    this.renderPagingNav = function() {
        var me = this;
        this.currentPageIdx = 0;
        this.totalPage = Math.floor(this.totalItem / this.ITEM_PER_PAGE);
        if (this.totalItem % this.ITEM_PER_PAGE > 0) {
            this.totalPage++;
        }
        $('.answer-list > .answer-item').hide();
        if (this.totalItem <= this.ITEM_PER_PAGE) {
            this.$pagingContainer.hide();
        } else {
            var htmls = [];
            htmls.push('<li><a class="request_paging" data-nav="first" href="#"><span>◀◀</span></a></li>');
            htmls.push('<li><a class="request_paging" data-nav="prev" href="#"><span>◀</span></a></li>');
            for (var idx = 0; idx < this.totalPage; idx++) {
                if (idx === 0) {
                    htmls.push('<li><a class="request_paging current" data-nav="' + idx + '" href="#" id="request_paging_' + idx + '">' + (idx + 1) + '</a></li>');
                } else {
                    htmls.push('<li><a class="request_paging" data-nav="' + idx + '" href="#" id="request_paging_' + idx + '">' + (idx + 1) + '</a></li>');
                }
            }
            htmls.push('<li><a class="request_paging" data-nav="next" href="#"><span>▶</span></a></li>');
            htmls.push('<li><a class="request_paging" data-nav="last" href="#"><span>▶▶</span></a></li>');
            this.$pagingContainer.html(htmls.join(''));
            this.$pagingContainer.show();
            $(".request_paging").click(function() {
                var navStep = $(this).data('nav');
                if (navStep == 'first') {
                    me.currentPageIdx = 0;
                } else if (navStep == 'prev') {
                    if (me.currentPageIdx > 0) {
                        me.currentPageIdx--;
                    }
                } else if (navStep == 'last') {
                    me.currentPageIdx = me.totalPage - 1;
                } else if (navStep == 'next') {
                    if (me.currentPageIdx < me.totalPage - 1) {
                        me.currentPageIdx++;
                    }
                } else {
                    me.currentPageIdx = parseInt(navStep);
                }
                $('.answer-list > .answer-item').hide();
                me.showDataPaging();
                $(".request_paging").removeClass("current");
                $("#request_paging_" + me.currentPageIdx).addClass("current");
                return false;
            });
        }
        if (this.totalItem > 0) {
            this.showDataPaging();
        }
    };

    this.showDataPaging = function() {
        var itemList = $('.answer-list > .answer-item');
        var startIdx = this.ITEM_PER_PAGE * this.currentPageIdx;
        var endIdx = this.ITEM_PER_PAGE * (this.currentPageIdx + 1);
        if (endIdx > this.totalItem) {
            endIdx = this.totalItem;
        }
        for (var idx = startIdx; idx < endIdx; idx++) {
            $(itemList[this.filterItemIndexs[idx]]).show();
        }
    };
};
hotro.UploadFileHandler = function() {
    this.maxUploadSize = 4;
    this.allowedExtensions = '';

    this.init = function(maxUploadSize, allowedExtensions) {
        if (maxUploadSize) {
            this.maxUploadSize = maxUploadSize;
        }
        if (allowedExtensions) {
            this.allowedExtensions = allowedExtensions;
        }

        var me = this;
        $('.upload-container input[type="file"]').change(function() {
            var fileCount = me.check_upload_file();
            var idx = $(this).data('index');
            if (fileCount) {
                var ext = me.get_ext(idx);
                $('.alert-warning').addClass('hide');
                if (ext == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ||
                    ext == 'application/msword') // word
                    $('.upload-preview').eq(idx).attr("src", "/static/img/attachment-word.png");
                else if (ext == 'application/pdf')
                    $('.upload-preview').eq(idx).attr("src", "/static/img/attachment-pdf.png");
                else if (ext == 'rar' || ext == "zip")
                    $('.upload-preview').eq(idx).attr("src", "/static/img/attachment-rar.png");
                else
                    me.loadPreview($(this)[0].files[0], idx);

                $(".att-remove").eq(idx).removeClass('hide');
            } else {
                $(this).val("");
                $('.upload-preview').eq(idx).attr("src", "/static/img/attachment-empty.png");
                $(".att-remove").eq(idx).addClass('hide');
            }
        });
    };

    this.check_upload_file = function() {
        var files = [];
        $(".input-file input[type='file']").each(function() {
            for (idx in $(this)[0].files) {
                var file = $(this)[0].files[idx];
                if (file instanceof File) {
                    files.push(file);
                }
            }
        });
        for (i = 0; i < files.length; i++) {
            if (files[i].size > parseInt(this.maxUploadSize) * 1024 * 1024) {
                $('#alert_upload').text("Kích thước tập tin đính kèm vượt quá giới hạn (" + this.maxUploadSize + "MB)");
                $('.alert-warning').removeClass('hide');
                jQuery('html,body').animate({
                    scrollTop: 0
                }, 0);
                return false;
            }

            val = files[i].name.toLowerCase();
            var regex = new RegExp("(.*?)\.(" + this.allowedExtensions.join('|') + ")$");
            if (!(regex.test(val))) {
                $('#alert_upload').text("Chỉ hỗ trợ các file: " + this.allowedExtensions.join(', '));
                $('.alert-warning').removeClass('hide');
                jQuery('html,body').animate({
                    scrollTop: 0
                }, 0);
                return false;
            }
        }
        return files.length > 0 ? files.length : true;
    };

    this.get_ext = function($id) {
        var files = [];
        $(".input-file input[type='file']").each(function() {
            for (idx in $(this)[0].files) {
                var file = $(this)[0].files[idx];
                if (file instanceof File) {
                    files.push(file);
                }
            }
        });

        if (files[$id]) {
            return files[$id].type;
        }
        return false;
    };

    this.loadPreview = function(file, index) {
        var reader = new FileReader();
        var $img = $('.upload-preview').eq(index);
        reader.onload = function(e) {
            $img.attr('src', e.target.result);
        }
        try {
            reader.readAsDataURL(file);
        } catch (e) {}
    };
};

hotro.RequestHandler = function() {
    this.data = {};
    this.init = function(data) {
        // $('.date-time-picker').datetimepicker({});
        // $('.date-picker').datepicker({
        //     autoclose: true,
        //     todayHighlight: true,
        //     format: 'dd/mm/yyyy',
        // });
        // $('.time-picker').timepicker({
        //         minuteStep: 5,
        //         secondStep: 15,
        //         showSeconds: true,
        //         showMeridian: false,    // true: 12hr mode / false: 24hr mode
        //         defaultTime: false      // 'current' / '12:45 PM' / false
        // });
        $(".col-sm-9").removeClass("col-sm-9");
        //         $(".date-time-picker").css("width", "480px");
        //         $(".date-picker").css("width", "480px");
        //         $(".time-picker").css("width", "480px");
        if (data) {
            try {
                this.data = JSON.parse(decodeURI(atob(data)));
            } catch (err) {
                console.log(err);
            }
        }
        this.fillLevelData();
        var me = this;

        if (formData !== null) {
            var formLen = formData.length;
            for (var i = 0; i < formLen; i++) {
                var dataObj = formData[i];
                if (['phone', 'email', 'name'].indexOf(dataObj.name) < 0) {
                    $("#request_form [name='" + dataObj.name + "']").val(dataObj.value);
                }
            }
        }
        // this.hidePersonalInfo();

        $("#request_form").submit(function() {
            $(this).submit(function() {
                return false;
            });
            return true;
        });
    };

    this.hidePersonalInfo = function() {
        var user_phone_placeholder = $("#user_phone_placeholder").val();
        var user_email_placeholder = $('#user_email_placeholder').val();
        var user_name_placeholder = $('#user_name_placeholder').val();

        if (user_phone_placeholder) {
            $("#request_form [name='phone']").attr('placeholder', user_phone_placeholder);
        } else {
            var labelEle = $("#request_form [for='phone']");
            labelEle.text(labelEle.text() + '*');
            $("#request_form [name='phone']").attr('required', 'required');
        }
        if (user_email_placeholder) {
            $("#request_form [name='email']").attr('placeholder', user_email_placeholder);
        } else {
            var labelEle = $("#request_form [for='email']");
            labelEle.text(labelEle.text() + '*');
            $("#request_form [name='email']").attr('required', 'required');
        }
        if (user_name_placeholder) {
            $("#request_form [name='name']").attr('placeholder', user_name_placeholder);
        } else {
            var labelEle = $("#request_form [for='name']");
            labelEle.text(labelEle.text() + '*');
            $("#request_form [name='name']").attr('required', 'required');
        }
    };

    this.fillLevelData = function() {
        // Inputed data
        $("#phone").val(this.data.phone);
        $("#email").val(this.data.email);
        $("#name").val(this.data.name);
        $("#title").val(this.data.title);
        $("#detail").val(this.data.detail);
    };

    this.getInputData = function() {
        var data = {
            phone: $("#phone").val(),
            email: $("#email").val(),
            name: $("#name").val(),
            title: $("#title").val(),
            detail: $("#detail").val(),
        };
        return btoa(encodeURI(JSON.stringify(data)));
    };

};

hotro.NotifyHandler = function() {
    this.init = function(redirect_url) {
        this.notify(redirect_url);
    };

    this.notify = function(redirect_url) {
        var notifyModal = $('.notifyModal');
        if (notifyModal.length) {
            notifyModal.modal({
                backdrop: 'static',
                keyboard: false
            });
        }
        $(".notifyModal .close").click(function() {
            $('.notifyModal').modal("hide");
            if (redirect_url) {
                window.location.href = redirect_url;
            }
        });
        // End verify code popup
    }

};

function removeUpload(e) {
    $container = $(e).prev();
    $container.find('input').val("");
    $container.find('img').attr("src", "/static/img/attachment-empty.png");
    $(e).addClass('hide');
}