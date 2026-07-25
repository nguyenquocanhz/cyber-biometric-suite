jQuery(document).ready(function($) {

    $('.menu-toggle').click(function(e) {
        e.preventDefault();
        console.log(2)
        $('body').toggleClass('nav-opened');
    })

    $('.nav-close').click(function(e) {
        e.preventDefault();
        $('body').removeClass('nav-opened');
    })

    $('.open-game-list').click(function(e) {
        e.preventDefault();
        $('.game-list').toggleClass('active');
    })

    $('.panel-item').each(function() {
        var elem = $(this);
        elem.find('.panel-head').click(function(e) {
            e.preventDefault();
            if (elem.hasClass('active')) {
                elem.removeClass('active')
            } else {
                $('.panel-item').removeClass('active');
                $('.panel-item').find('.panel-content').slideUp(250)
                elem.addClass('active');
            }
            var el = $(this);
            el.next().slideToggle(250);
        })
    })

    $('.selection').each(function() {
        var elem = $(this);

        elem.find('select').change(function(e) {
            elem.find('span').text($(this).find('option:selected').text())
        })
    })

    $('.open-modal').click(function(e) {
        e.preventDefault();
        var elem = $(this);
        var target = elem.attr('href');

        $(target).addClass('show');
    });

    $('.close-modal').click(function(e) {
        e.preventDefault();
        closeModal();
    });

    // $('.modal').click(function(e){
    //   $(this).removeClass('show');
    // })
    $('.modal-content').click(function(e) {
        e.stopPropagation();
    });

    $('.show-pre-form-notice').click(function(e) {
        e.preventDefault();
        $('.show-pre-form-notice').hide();
        $('.pre-form-notice').show();
    })

    $('.show-form-advanced').click(function(e) {
        e.preventDefault();
        $('.form-support').addClass('active');
    })

    //$(window).trigger('resize');
});
// $(document).on('keyup', function(e){
//   if(e.which == 27) {
//     closeModal();
//   }
// })
function closeModal() {
    $('.modal').removeClass('show');
}
$(window).resize(function() {
    /*var e = $(window).width();
        e >= 1920 ? $("html").css("font-size", "10px") : e >= 1400 ? $("html").css("font-size", 10 * e / 1920 + "px") : e >= 767 ? $("html").css("font-size", "10px") : $("html").css("font-size", 10 * e / 767 + "px")*/
});
'use strict';

(function() {
    var SECTION_SELECTOR = '.ff-section';
    var OPTION_SELECTOR = '.ff-option';
    var SHOW_CLASS = 'ff-show';
    var ACTIVE_CLASS = 'ff-active';

    var init = function init() {
        /* $(`${SECTION_SELECTOR}:not([data-ffid])`).each((idx, ele) => {
          let $ele = $(ele)
          $ele.addClass(SHOW_CLASS)
        }) */
    };

    var select = function select(selectOption) {
        var $selectOption = $(selectOption);
        var $section = $selectOption.closest(SECTION_SELECTOR);
        if (!$section) {
            return;
        }
        $section.find(OPTION_SELECTOR).each(function(idx, option) {
            var $option = $(option);
            if ($option.is($selectOption)) {
                activate($option);
            } else {
                deactivate($option);
            }
        });
        $selectOption.addClass(ACTIVE_CLASS);
    };

    var getId = function getId($ele) {
        return String($ele.data('ffid'));
    };

    var visible = function visible($section) {
        var ffidSet = getId($section).split('|').map(function(set) {
            return set.trim();
        }).filter(function(set) {
            return set;
        });
        var _iteratorNormalCompletion = true;
        var _didIteratorError = false;
        var _iteratorError = undefined;

        try {
            for (var _iterator = ffidSet[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
                var set = _step.value;

                var ffids = set.split(' ').filter(function(ffid) {
                    return ffid;
                });
                if (ffids.every(function(ffid) {
                        return $(OPTION_SELECTOR + '.' + ACTIVE_CLASS + '[data-ffid~="' + ffid + '"]').length > 0;
                    })) {
                    return true;
                }
            }
        } catch (err) {
            _didIteratorError = true;
            _iteratorError = err;
        } finally {
            try {
                if (!_iteratorNormalCompletion && _iterator.return) {
                    _iterator.return();
                }
            } finally {
                if (_didIteratorError) {
                    throw _iteratorError;
                }
            }
        }

        return false;
    };

    var toggle = function toggle($option) {
        var ffids = getId($option).split(' ').filter(function(ffid) {
            return ffid;
        });
        ffids.forEach(function(changed_ffid) {
            $(SECTION_SELECTOR + '[data-ffid~="' + changed_ffid + '"]').each(function(idx, section) {
                var $section = $(section);
                if (visible($section)) {
                    show($section);
                } else {
                    hide($section);
                }
            });
        });
    };

    var deactivate = function deactivate($option) {
        $option.removeClass(ACTIVE_CLASS);
        toggle($option);
    };

    var activate = function activate($option) {
        $option.addClass(ACTIVE_CLASS);
        toggle($option);
    };

    var show = function show($section) {
        return $section.addClass(SHOW_CLASS);
    };

    var hide = function hide($section) {
        if ($section.hasClass(SHOW_CLASS)) {
            $section.removeClass(SHOW_CLASS);
            $section.find(OPTION_SELECTOR).each(function(idx, option) {
                var $option = $(option);
                deactivate($option);
            });
        }
    };

    $(document).ready(function() {
        init();
        $('body').on('click', OPTION_SELECTOR, function(event) {
            $(window).scrollTo('.ff-option:last', 500);
            return select(event.target);
        });
    });
})();