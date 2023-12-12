$(document).ready(function() {
    function updateFormHandlerElements() { 
        // things that we have to update again if items are removed/added
        
        $('.formHandlerForm [data-toggle="popover"]').popover({ container: 'body', placement: "right", trigger: "focus" });
        
        $.Topic('formHandler.updateFormHandlerElements').publish('update'); // broadcast event to any interested listeners
    }
    
    updateFormHandlerElements();
    
    // Search Options Collapse
    
    $('#searchOptions').on('shown.bs.collapse', function () {
        $('#showHideSearchOptions i').replaceWith('<i class="fa fa-caret-square-o-up"></i>');
    });
    $('#searchOptions').on('hidden.bs.collapse', function () {
        $('#showHideSearchOptions i').replaceWith('<i class="fa fa-caret-square-o-down"></i>');
    });

    // Checkboxes "Search All"
    
    $('.formHandlerForm .checkboxes input.searchAll').change(function(event) {
        if ($(this).prop('checked'))
            $(this).closest('.checkboxes').find('input:not(.searchAll)').prop('checked',false);
    });
    $('.formHandlerForm .checkboxes input:not(.searchAll)').change(function(event) {
        if ($(this).prop('checked'))
            $(this).closest('.checkboxes').find('input.searchAll').prop('checked',false);
    });
    
    /*Autofocus

    $('.formHandlerForm input[type="text"]').first().focus(); (causes page to jump down, enable for some pages?)*/
    
    // Multi Input
  
    if (typeof fhUsingMulti !== 'undefined') {
        $('.formHandlerForm').on('change', '.fhMulti_key', function() {
            var valElement = $(this).parent().parent().find('.fhMulti_value');
            valElement.attr('name', valElement.data('fhVarName') + "[" + $(this).val() + "]");
        });
        $('.formHandlerForm').on('click', 'a.fhMulti_remove', function(e) {
            $(this).parent().parent().remove();
            e.preventDefault();
        });
        $('.formHandlerForm').on('click', 'a.fhMulti_add', function (e) {
            $(this).closest('tr').before("<tr>" + $(this).closest('table').find(".fhMulti_template").html() + "</tr>");
            updateFormHandlerElements();
            e.preventDefault();
        });
    }
    
    // Date Pickers
  
    if (typeof fhDatePickers !== 'undefined') {
        for (var num in fhDatePickers) {
            $("#fhDatepicker" + num).datepicker(fhDatePickers[num]);
        }
    }
    
    // Tabs
    
    $('.formTabs a').click(function (e) {
        var href = $(this).attr("href");

        /*(nevermind, decided not to do it this way because we would also have to exclude "tabClicked" from pagination links, etc.)
            Set showSearchOptions to tell it not to hide the search form as some of our forms do after searching.
            $(this).parent().append($("<input>", { type: "hidden", name: "tabClicked", value: "1" }));*/

        // set hidden value so the form also submits the tab value when submitting the form
        $(this).closest('ul').siblings('input[type="hidden"]').val(href.substr(href.indexOf("#")+1)).trigger("change");
    });
    
    // submitFormOnChange
    
    $('[fhSubmitFormOnChange] input, [fhsubmitFormOnChange] select').change(function (e) {
        e.preventDefault();
        $(this).closest('form').submit();
    });
    
    // Dynamic
    
    if (typeof fhUsingClientSideDynamic !== 'undefined') {
        
        var fhDynamicElements = { };
        var positionCount = 0;
        
        function determineDynamicGroupValue($determinerBlock) {
            var $inputElement = $('input', $determinerBlock).first();
            if ($inputElement.length) {
                if ($inputElement.attr('type') == 'radio') {
                    return $determinerBlock.find('input:checked').first().val(); //radio button
                } else {
                    return $inputElement.val(); //regular input
                }
            }
            $inputElement = $('select', $determinerBlock).first();
            if ($inputElement.length) return $inputElement.val(); //select
            return null;
        }
        
        function placeDynamicElements(dynamicGroupName, dynamicGroupValue) {
            if (!(dynamicGroupName in fhDynamicElements)) return;
            var dynamicGroup = fhDynamicElements[dynamicGroupName];
            
            for (var i = 0; i < dynamicGroup.length; i++) {
                if (dynamicGroup[i].method == 'hide') {
                    if ($.inArray(dynamicGroupValue, dynamicGroup[i].values) != -1) {
                        $('span#'+dynamicGroup[i].positionKey).show();
                    } else {
                        $('span#'+dynamicGroup[i].positionKey).hide();
                    }
                } else if (dynamicGroup[i].method == 'remove' || dynamicGroup[i].method == '') {
                    if ($.inArray(dynamicGroupValue, dynamicGroup[i].values) != -1) {
                        $('span#'+dynamicGroup[i].positionKey).html(dynamicGroup[i].html);
                    } else {
                        $('span#'+dynamicGroup[i].positionKey).html(''); //remove elements that don't match this value
                    }
                }
            }
            
            updateFormHandlerElements();
        }
 
        $('span[data-dynamic-group]').each(function( index ) {
            var positionKey = 'fhDynamicGroup'+(positionCount++);
            var method = $(this).data('dynamicMethod');
            if (method == '') method = 'remove';
            var dynamicGroup = $(this).data('dynamicGroup');
            
            if (!(dynamicGroup in fhDynamicElements)) fhDynamicElements[dynamicGroup] = [ ];
            
            if (method == 'remove') {
                var element = $(this).replaceWith('<span id="'+positionKey+'"></span>');

                fhDynamicElements[dynamicGroup].push({
                    values : $(element).data('dynamicGroupValues').toString().split(","),
                    positionKey : positionKey,
                    html : $(element).html(),
                    method : 'remove'
                });
            } else if (method == 'hide') {
                $(this).attr('id', positionKey);
                fhDynamicElements[dynamicGroup].push({
                    values : $(this).data('dynamicGroupValues').toString().split(","),
                    positionKey : positionKey,
                    method : 'hide'
                });
            }
        });
        
        // Use initial value to place the current dynamic elements
        $('[data-fh-determines-dynamic-group]').each(function( index ) {
            placeDynamicElements($(this).data('fhDeterminesDynamicGroup'), determineDynamicGroupValue($(this)));
        });
        
        // Change Event Handler
        $('[data-fh-determines-dynamic-group] input, [data-fh-determines-dynamic-group] select').change(function () {
            placeDynamicElements($(this).closest('[data-fh-determines-dynamic-group]').data('fhDeterminesDynamicGroup'), $(this).val());
        });
    }
    
    
    // lastOptionStringInput

    function lastOptionStringInputUpdateValue(stringInput, radioInput) {
        var v = $(stringInput).val();
        // If no value is entered, we use the option key name (which was saved as 'lastOptionStringInputDefaultValue' as the value.
        if (v == '') v = $(radioInput).attr('lastOptionStringInputDefaultValue');
        $(radioInput).val(v);
    }
    
    function lastOptionStringInputUpdateAll() {
        $('.lastOptionStringInput input[type=radio]:checked').siblings('span').show();
        $('.lastOptionStringInput input[type=radio]:not(:checked)').siblings('span').hide();
        
        // Set the value of the radio button to the value of the input string.
        $('.lastOptionStringInput input[type=radio]:checked').each(function() {
            lastOptionStringInputUpdateValue($(this).siblings('span').children('input'), this);
        });
    }
    
    lastOptionStringInputUpdateAll();
        
    // Note: It has to monitor the entire radio button group for changes for change() to detect an de-select of a radio button.
    $('.lastOptionStringInput').parent().parent().find('input[type=radio]').change(function(e) {
        lastOptionStringInputUpdateAll();
    });
    
    // Keep the radio button value updated to equal the input string's value.
    $('.lastOptionStringInput input[type=text]').keyup(function(e) {
        lastOptionStringInputUpdateValue(this, $(this).parent().siblings('input[type=radio]'));
    });
});
