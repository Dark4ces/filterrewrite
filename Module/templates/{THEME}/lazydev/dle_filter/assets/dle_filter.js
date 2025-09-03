$(function() {
    $('[data-dlefilter*=dle-filter] input, [data-dlefilter*=dle-filter] textarea').keypress(function (e) {
        if (e.which == 13) {
            e.preventDefault();
            filterAjax($(this).closest('form'), true);
        }
    });

    let setFilterParam = function () {
        let filterParam = decodeURIComponent(location.href).split('/f/');
        if (!filterParam[1]) {
            return;
        }
        if (filterParam[1].slice(-1) === '/') {
            filterParam[1] = filterParam[1].slice(0, -1);
        }

        filterParam = filterParam[1].split('/');
        let arrayParam = [];
        for (let i = 0; i < filterParam.length; i++) {
            arrayParam[i] = filterParam[i].split('=');
            if (arrayParam[i][1]) {
                arrayParam[i][0] = arrayParam[i][0].replace(/\+/g, ' ');
                arrayParam[i][1] = arrayParam[i][1].replace(/\+/g, ' ');
            }
        }

        $('[data-dlefilter*=dle-filter] input[type="text"], [data-dlefilter*=dle-filter] textarea').each(function () {
            let nameElem = $(this).attr('name');
            if (nameElem !== undefined && nameElem.length > 0) {
                for (let i = 0; i < arrayParam.length; i++) {
                    if (nameElem === arrayParam[i][0]) {
                        if (nameElem.indexOf('r.') + 1) {
                            let slider = $(this).data('ionRangeSlider');
                            let sliderData = arrayParam[i][1].split(';');
                            slider.update({
                                from: sliderData[0],
                                to: sliderData[1]
                            });
                        } else {
                            $(this).val(arrayParam[i][1]);
                        }
                    }
                }
            }
        });

        $('[data-dlefilter*=dle-filter] select').each(function (index) {
            let nameElem = $(this).attr('name');
            if (nameElem !== undefined && nameElem.length > 0) {
                for (let i = 0; i < arrayParam.length; i++) {
                    if (nameElem === arrayParam[i][0]) {
                        let selectData = arrayParam[i][1].split(',');

                        $(this).find('option').each(function (s, n) {
                            if ($.inArray(n.value, selectData) >= 0) {
                                $(this).attr('selected', true);
                            }
                        });
                    }
                }

                if (dleFilter.tail == 1) {
                    let getTail = $('.tail-select');
                    if (getTail.length > 0) {
                        tail.select('[data-dlefilter*=dle-filter] select[data-select-id="' + index + '"]').reload();
                    }
                }
            }
        });

        if (dleFilter.chosenSelect == 1) {
            let getChosen = $('.chosen-results');
            if (getChosen.length > 0) {
                $('[data-dlefilter*=dle-filter] select').trigger('chosen:updated');
            }
        }

        if (dleFilter.niceSelect == 1) {
            $('[data-dlefilter*=dle-filter] select').niceSelect('update');
        }

        $('[data-dlefilter*=dle-filter] input[type="radio"], [data-dlefilter*=dle-filter] input[type="checkbox"]').each(function () {
            let nameElem = $(this).attr('name');
            if (nameElem !== undefined && nameElem.length > 0) {
                for (let i = 0; i < arrayParam.length; i++) {
                    if (nameElem === arrayParam[i][0]) {
                        let selectData = arrayParam[i][1].split(',');

                        $(this).each(function (s, n) {
                            if ($.inArray(n.value, selectData) >= 0) {
                                $(this).attr('checked', 'checked');
                            }
                        });
                    }
                }
            }
        });
    };

    let filterClear = function () {
        dleFilter.reset = true;

        let formFilter = $(this).closest('form');
        let formId = $(formFilter).data('dlefilter');

        if (dleFilter.ajaxUrl == 0) {
            history.pushState(null, dleFilter.title, dleFilter.path);
            document.title = dleFilter.title;
        }

        if (dleFilter.path.indexOf('/f/') + 1) {
            $('#dle-content').html(dleFilter.content);
        } else {
            if (dleFilter.id == 'dle-content') {
                $('#dle-content').html(dleFilter.content);
            } else {
                $('#' + dleFilter.id).html(dleFilter.original[dleFilter.id]);
            }
        }

        if (dleFilter.lazy == 1) {
            $('[data-src]').lazyLoadXT();
        }

        if ($('#dle-speedbar').length > 0) {
            $('#dle-speedbar').html(dleFilter.speedbar);
        }

        $(formFilter).find('input[type="text"]').each(function() {
            let nameElem = $(this).prop('name');
            if (nameElem.length > 0) {
                if (nameElem.indexOf('r.') + 1) {
                    let slider = $(this).data('ionRangeSlider');
                    slider.update({
                        from: slider.options.min,
                        to: slider.options.max
                    });
                } else {
                    $(this).val('');
                }
                if ($(this).data('dlefilter-show') !== undefined) {
                    showAndHideFilter(this);
                }
            }
        });

        $(formFilter).find('select').each(function(index) {
            let nameElem = $(this).prop('name');
            if (nameElem.length > 0) {
                $(this).children('option').each(function() {
                    $(this).attr('selected', false);
                });

                if (dleFilter.tail == 1) {
                    let getTail = $('.tail-select');
                    if (getTail.length > 0) {
                        tail.select('[data-dlefilter*="' + formId + '"] select[data-select-id="' + index + '"]').reload();
                    }
                }

                if ($(this).data('dlefilter-show') !== undefined) {
                    showAndHideFilter(this);
                }
            }
        });

        if (dleFilter.chosenSelect == 1) {
            let getChosen = $('.chosen-results');
            if (getChosen.length > 0) {
                $('[data-dlefilter*="' + formId + '"] select').trigger('chosen:updated');
            }
        }

        if (dleFilter.niceSelect == 1) {
            $('[data-dlefilter*="' + formId + '"] select').niceSelect('update');
        }

        $(formFilter).find('input[type="radio"], input[type="checkbox"]').each(function() {
            let nameElem = $(this).prop('name');
            if (nameElem.length > 0) {
                $(this).attr('checked', false);
                if ($(this).data('dlefilter-show') !== undefined) {
                    showAndHideFilter(this);
                }
            }
        });

        dleFilter.reset = false;
    };

    let filterWork = function(data) {
        data = $.parseJSON(data);

        if (dleFilter.ajax == 1) {
            window.location.href = data.url;
        } else {
            if ($('#dle-speedbar').length > 0) {
                $('#dle-speedbar').html($('#dle-speedbar', data.speedbar).html());
            }

            if (dleFilter.path.indexOf('/f/') + 1) {
                $('#dle-content').html(data.content);
            } else {
                $('#' + dleFilter.id).html(data.content);
            }

            if (dleFilter.lazy == 1) {
                $('[data-src]').lazyLoadXT();
            }

            if (dleFilter.ajaxUrl == 0) {
                history.pushState(null, data.title, data.url);
                document.title = data.title;
            }
        }
    };

    let filterAjax = function(elem, enter) {
        if (dleFilter.reset) {
            return;
        }

        enter = enter || false;
        let data, elemTag, elemType = '';

        if (enter === true) {
            data = $(elem).serialize();
        } else {
            elemTag = elem.target.tagName.toUpperCase();
            if (elem.target.type !== undefined) {
                elemType = elem.target.type.toUpperCase();
            }
            if (elemTag === 'TEXTAREA' || elemTag === 'INPUT' && (elemType === 'TEXT' || elemType === 'RESET')) {
                return;
            }
            data = elemTag !== 'FORM' ? $(this).closest('form').serialize() : $(this).serialize();
        }

        dleFilter.id = (elemTag !== 'FORM' ? $(this).closest('form').data('dlefilter-id') : $(this).data('dlefilter-id')) || 'dle-content';
        if (!dleFilter.original[dleFilter.id] && dleFilter.id != 'dle-content') {
            dleFilter.original[dleFilter.id] = $('#' + dleFilter.id).html();
        }

        $.ajax({
            beforeSend: function() {
                if (dleFilter.hideLoading == 0) {
                    ShowLoading('');
                }
            },
            url: dle_root + 'engine/lazydev/dle_filter/ajax.php',
            type: 'POST',
            data: {
                data: data,
                url: dleFilter.path,
                dle_hash: dle_login_hash,
            },
            success: function(output) {
                if (output.error) {
                    DLEalert(output.text, dle_info);
                } else {
                    filterWork(output);
                }
            },
            error: function(output) {
                DLEalert(output.responseText, dle_info);
            }
        }).always(function() {
            if (dleFilter.hideLoading == 0) {
                HideLoading('');
            }
        });
    };

    if ($('#dle-speedbar').length > 0) {
        dleFilter.speedbar = $('#dle-speedbar').html();
    }

    dleFilter.content = $('#dle-content').html();

    $('body').on('click', '[data-dlefilter=submit]', filterAjax);

    if (dleFilter.button == 0) {
        $('body').on('change', '[data-dlefilter*=dle-filter]', filterAjax);
    }

    $('body').on('click', '[data-dlefilter=reset]', filterClear);

    $('[data-dlefilter*=dle-filter]').find('input[type="text"][name*="r."]').each(function() {
        let sliderVars = $(this).data('slider-config');
        if (sliderVars !== undefined && sliderVars.length > 0) {
            let sliderConfig = {};
            if (sliderVars.slice(-1) == ';') {
                sliderVars = sliderVars.slice(0, -1);
            }
            sliderVars = sliderVars.split(';');
            for (let i = 0; i < sliderVars.length; i++) {
                let attempt = sliderVars[i].split(':');
                attempt[0] = attempt[0].trim();
                switch (attempt[0]) {
                    case 'АвтоПолзунок':
                        sliderConfig.slider = true;
                        break;
                    case 'Одиночный слайдер':
                        sliderConfig.type = 'single';
                        break;
                    case 'Двойной слайдер':
                        sliderConfig.type = 'double';
                        break;
                    case 'Минимальное значение':
                        if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
                            sliderConfig.min = attempt[1];
                        }
                        break;
                    case 'Максимальное значение':
                        if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
                            sliderConfig.max = attempt[1];
                        }
                        break;
                    case 'Начало слайдера':
                        if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
                            sliderConfig.from = attempt[1];
                        }
                        break;
                    case 'Конец слайдера':
                        if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
                            sliderConfig.to = attempt[1];
                        }
                        break;
                    case 'Шаг':
                        if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
                            sliderConfig.step = attempt[1];
                        }
                        break;
                    case 'Шаблон':
                        if (attempt[1] !== undefined && attempt[1] !== '') {
                            sliderConfig.skin = attempt[1];
                        }
                        break;
                    case 'Префикс':
                        if (attempt[1] !== undefined && attempt[1] !== '') {
                            sliderConfig.prefix = attempt[1];
                        }
                        break;
                    case 'Постфикс':
                        if (attempt[1] !== undefined && attempt[1] !== '') {
                            sliderConfig.postfix = attempt[1];
                        }
                        break;
                    case 'Сетка':
                        sliderConfig.grid = true;
                        break;
                    case 'Красивые числа':
                        sliderConfig.prettify_enabled = true;
                        break;
                    case 'Скрыть MinMax':
                        sliderConfig.hide_min_max = true;
                        break;
                    case 'Скрыть FromTo':
                        sliderConfig.hide_from_to = true;
                        break;
                }
            }

            if (!sliderConfig.prettify_enabled) {
                sliderConfig.prettify_enabled = false;
            }

            if (sliderConfig.slider) {
                sliderConfig.onFinish = function (data) {
                    filterAjax($('[name="' + data.input[0].name + '"]').closest('form'), true);
                };
            }

            $(this).ionRangeSlider(sliderConfig);
        }
    });

    if (dleFilter.tail == 1) {
        $('[data-dlefilter*=dle-filter]').find('select').each(function(index) {
            $(this).attr('data-select-id', index);
            let selectVars = $(this).data('select-config');
            let nameElemSelect = $(this).attr('name');
            let selectConfig = {};
            if (selectVars !== undefined && selectVars.length > 0) {
                if (selectVars.slice(-1) == ';') {
                    selectVars = selectVars.slice(0, -1);
                }
                selectVars = selectVars.split(';');
                for (let i = 0; i < selectVars.length; i++) {
                    let attempt = selectVars[i].split(':');
                    switch (attempt[0]) {
                        case 'Поиск':
                            selectConfig.search = true;
                            break;
                        case 'Скрыть выбранные':
                            selectConfig.hideSelected = true;
                            break;
                        case 'Максимум выбрать':
                            if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
                                selectConfig.multiLimit = attempt[1];
                            }
                            break;
                        case 'Показать количество выбранных':
                            if (!selectConfig.multiContainer) {
                                selectConfig.multiShowCount = true;
                            }
                            break;
                        case 'Вывод выбранных':
                            selectConfig.multiContainer = true;
                            if (selectConfig.multiShowCount) {
                                selectConfig.multiShowCount = false;
                            }
                            break;
                        case 'Подсказка':
                            if (attempt[1] !== undefined && attempt[1] !== '') {
                                selectConfig.placeholder = attempt[1];
                            }
                            break;
                        case 'Сортировать':
                            if (attempt[1] !== undefined && (attempt[1] === 'ASC' || attempt[1] === 'DESC')) {
                                selectConfig.sortItems = attempt[1];
                            }
                            break;
                    }
                }
            }
            tail.select('[data-dlefilter*=dle-filter] select[data-select-id="' + index + '"]', selectConfig).on('change', function(item, state) {
                if (dleFilter.button == 0) {
                    $('[name="' + item.option.parentElement.name + '"]').trigger('change');
                }
            });
        });
    }

    if (dleFilter.chosenSelect == 1) {
        $('[data-dlefilter*=dle-filter] select').chosen();
    }

    if (dleFilter.niceSelect == 1) {
        $('[data-dlefilter*=dle-filter] select').niceSelect();
    }

    if (dleFilter.ajaxNav == 1) {
        $('body').on('click', 'a[href*="/f/"]', function(e) {
            e.preventDefault();
            let urlPage = $(this).attr('href');
            if (urlPage !== undefined) {
                $.ajax({
                    url: urlPage,
                    beforeSend: function() {
                        if (dleFilter.hideLoading == 0) {
                            ShowLoading('');
                        }
                    },
                    success: function(output) {
                        if ($('#dle-speedbar').length > 0) {
                            $('#dle-speedbar').html($('#dle-speedbar', output).html());
                        }

                        if (dleFilter.path.indexOf('/f/') + 1) {
                            $('#dle-content').html($('#dle-content', output).html());
                        } else {
                            $('#' + dleFilter.id).html($('#dle-content', output).html());
                        }

                        if (dleFilter.lazy == 1) {
                            $('[data-src]').lazyLoadXT();
                        }

                        if (dleFilter.ajaxUrl == 0) {
                            window.history.pushState('', '', urlPage);
                            document.title = dleFilter.title;
                        }

                        $('html, body').animate({
                            scrollTop: $('#' + dleFilter.id).offset().top
                        }, 2000);
                    },
                    error: function(output) {
                        DLEalert(output.responseText, dle_info);
                    }
                }).always(function() {
                    if (dleFilter.hideLoading == 0) {
                        HideLoading('');
                    }
                });
            }

            return false;
        });
    }

    Array.prototype.diff = function(a) {
        return this.filter(function(i) {return a.indexOf(i) < 0;});
    };

    let showAndHideFilter = function(e) {
        let elemTag = $(e).prop('tagName').toUpperCase();
        let elemType = $(e).prop('type').toUpperCase();

        let showNode = $(e).data('dlefilter-show');
        let objShow = {};
        if (showNode.slice(-1) == ';') {
            showNode = showNode.slice(0, -1);
        }
        showNode = showNode.split(';');
        $.each(showNode, function(index, value) {
            let tempObj = value.split(':');
            objShow[tempObj[0]] = tempObj[1].split(',');
        });

        let showList = {};
        let hideList = {};

        if (elemTag === 'SELECT') {
            $(e).find('option').each(function(p, elem) {
                $.each(objShow, function(index, value) {
                    if ($(elem).prop('selected') && $.inArray($(elem).val(), value) >= 0) {
                        showList[index] = '[data-dlefilter-hide="' + index + '"]';
                    } else {
                        hideList[index] = '[data-dlefilter-hide="' + index + '"]';
                    }
                });
            });
        } else if (elemTag === 'INPUT') {
            $.each(objShow, function(index, value) {
                if (elemType === 'TEXT') {
                    if ($.inArray($(e).val(), value) >= 0) {
                        showList[index] = '[data-dlefilter-hide="' + index + '"]';
                    } else {
                        hideList[index] = '[data-dlefilter-hide="' + index + '"]';
                    }
                } else if (elemType === 'RADIO' || elemType === 'CHECKBOX') {
                    if ($(e).prop('checked') && $.inArray($(e).val(), value) >= 0) {
                        showList[index] = '[data-dlefilter-hide="' + index + '"]';
                    } else {
                        hideList[index] = '[data-dlefilter-hide="' + index + '"]';
                    }
                }
            });
        } else if (elemTag === 'TEXTAREA') {
            $.each(objShow, function(index, value) {
                if ($.inArray($(e).val(), value) >= 0) {
                    showList[index] = '[data-dlefilter-hide="' + index + '"]';
                } else {
                    hideList[index] = '[data-dlefilter-hide="' + index + '"]';
                }
            });
        }

        let whatHide = Object.values(hideList).diff(Object.values(showList));
        if (whatHide) {
            $.each(whatHide, function(index, value) {
                $(value).find('input[type="text"]').each(function() {
                    let nameElem = $(this).prop('name');
                    if ($(this).data('dlefilter-show') !== undefined) {
                        showAndHideFilter(this);
                    }
                    if (nameElem.length > 0) {
                        if (nameElem.indexOf('r.') + 1) {
                            let slider = $(this).data('ionRangeSlider');
                            slider.update({
                                from: slider.options.min,
                                to: slider.options.max
                            });
                        } else {
                            $(this).val('');
                        }
                    }
                });

                $(value).find('select').each(function(index) {
                    let nameElem = $(this).prop('name');
                    if (nameElem.length > 0) {
                        if ($(this).data('dlefilter-show') !== undefined) {
                            showAndHideFilter(this);
                        }
                        $(this).children('option').each(function() {
                            $(this).attr('selected', false);
                        });

                        if (dleFilter.tail == 1) {
                            let getTail = $('.tail-select');
                            if (getTail.length > 0) {
                                tail.select('[data-dlefilter*=dle-filter] select[data-select-id="' + index + '"]').reload();
                            }
                        }
                    }
                });

                if (dleFilter.chosenSelect == 1) {
                    let getChosen = $('.chosen-results');
                    if (getChosen.length > 0) {
                        $('[data-dlefilter*=dle-filter] select').trigger('chosen:updated');
                    }
                }

                if (dleFilter.niceSelect == 1) {
                    $('[data-dlefilter*=dle-filter] select').niceSelect('update');
                }

                $(value).find('input[type="radio"], input[type="checkbox"]').each(function() {
                    let nameElem = $(this).prop('name');
                    if (nameElem.length > 0) {
                        if ($(this).data('dlefilter-show') !== undefined) {
                            showAndHideFilter(this);
                        }
                        $(this).attr('checked', false);
                    }
                });
            });
            $(whatHide.join(', ')).hide();
        }

        if (showList) {
            $(Object.values(showList).join(', ')).show();
        }
    };

    if (dleFilter.path.indexOf('/f/') + 1) {
        setFilterParam();
    }

    $('[data-dlefilter-hide]').hide();

    $('body').find('[data-dlefilter-show]').each(function(p, e) {
        showAndHideFilter(e);
    });

    $('body').on('change', '[data-dlefilter-show]', function(e) {
        showAndHideFilter(this);
    });

});