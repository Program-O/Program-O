/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.4
 * FILE: editAiml.js
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 05-11-2013
 * DETAILS: UI for aiml edition
 ***************************************/
var draw = 1;
var group = 1;
var scrollY;
var table;
$(function () {
    $('#showHelp').hide();
    $('#AIML').on('click', '.editable textarea', function (e) {
        e.stopPropagation();
    });
    $('#AIML').on('blur', '.editable textarea', function (e) {
        e.stopPropagation();
        var rowID = $(this).attr('data-rowID');
        var fieldName = $(this).attr('data-fieldName');
        var oldData = $(this).attr('data-oldData');
        var curData = $(this).val();
        console.log('current data =', curData, ', old data =', oldData);
        var dataText = $('<div>').html(oldData).text();
        if (oldData == curData || curData == dataText) return cancelEdit($(this));
        else if (typeof oldData !== 'undefined') {
            console.log('current data =', curData);
            console.log('old data =', oldData);
            saveEdit($(this));
            return false;
        }
        else return cancelEdit($(this));
    });
    $('#AIML').on('click', '.editable', function (e) {
        console.log('Clicked!');
        var cellID = $(this).closest('tr').attr('id');
        var rawClass = $(this).attr('class');
        var cellClass = rawClass.replace(' editable', '').replace(' sorting_1', '');
        console.log('Cell class =', cellClass);
        var editCell = buildTA($(this), cellClass, $(this).html());
        editCell.focus();
        editCell.on('keyup', function (e) {
            if (e.keyCode == 27) cancelEdit($(this));
        });
    });
    table = buildTable();
    $(window).on('resize', function () {
        if (typeof table === 'undefined') table = buildTable();
        scrollY = changeHeight();
        $('.holder').height(scrollY);
        table.draw(false);
    });
    $('.search-input-text').on('keyup click', function () {
        var i = $(this).data('column');
        var v = $(this).val();
        table.columns(i).search(v).draw(false);
    });
    $('#addNewCat').on('submit', function (e) {
        e.preventDefault();
        var fd = $(this).serialize();
        console.log('Form Data =', fd);
        $.ajax({
            url: 'editAJAX.php',
            type: 'post',
            dataType: 'text',
            data: fd,
            success: function (data) {
                $('#errMsg').html('<div class="closeButton" id="closeButton" onclick="closeStatus(\'errMsg\')" title="Click to hide">&nbsp;</div>').show();
                $('<span>').html(data).appendTo('#errMsg');
                table.draw(false);
                setTimeout(hideMsg, 3000);
                $('#btnClearNewCat').click();
            }
        });
    });
});

function showHide() {
    $('#showHelp').toggle();
}

function hideMsg() {
    $('#errMsg').fadeOut();
}

function cancelEdit(ele) {
    console.log('Cancelling the edit.');
    var rowID = ele.attr('data-rowID');
    var fieldName = ele.attr('data-fieldName');
    var oldData = ele.attr('data-oldData');
    ele.parent().empty().html(oldData);
}

function saveEdit(ele) {
    console.log('Saving the edit.');
    var rowID = ele.attr('data-rowID');
    var fieldName = ele.attr('data-fieldName');
    // insert the new value into the current cell
    var curVal = ele.val();
    ele.parent().text(curVal);
    var row = $('#' + rowID);
    var pattern = row.find('.pattern').text();
    var thatpattern = row.find('.thatpattern').text();
    var template = row.find('.template').text();
    var topic = row.find('.topic').text();
    var filename = row.find('.filename').text();
    // Now gether all of the fields and send the updated information
    $.ajax({
        url: 'editAJAX.php',
        type: 'post',
        dataType: 'text',
        data: {
            action: 'update',
            id: rowID,
            pattern: pattern,
            thatpattern: thatpattern,
            template: template,
            topic: topic,
            filename: filename
        },
        success: function (data) {
            $('#errMsg').empty().html('<div class="closeButton" id="closeButton" onclick="closeStatus(\'errMsg\')" title="Click to hide">&nbsp;</div>').show();
            $('<pre>').html(data).appendTo('#errMsg');
            //console.log('Foo!');
            setTimeout(hideMsg, 3000);
            table.draw(false);
        }
    });
}

function buildTA(ele, eleName, data) {
    var rowID = ele.closest('tr').attr('id');
    var w = ele.width() - 6;
    var name = eleName.replace(' ', '');
    var dataText = $('<div>').html(data).text();
    var field = $('<textarea>').attr('name', name).val(dataText).css({
        width: w + 'px',
        height: '100%',
        marginLeft: '-16px',
        marginTop: '3px'
    });
    field.attr('data-fieldName', name);
    field.attr('data-rowID', rowID);
    field.attr('data-oldData', data);
    ele.empty().append(field);
    return field;
}

function deleteRow(ele) {
    console.log('Delete Event Detected!');
    var verify = confirm('Are you sure that you wish to delete this row of data? This action cannot be undone!');
    if (!verify) return false;
    var id = ele.closest('tr').attr('id');
    console.log('ID =', id);
    $.ajax({
        url: 'editAJAX.php',
        type: 'post',
        dataType: 'text',
        data: {
            action: 'del',
            id: id
        },
        success: function (data) {
            $('#errMsg').empty().html('<div class="closeButton" id="closeButton" onclick="closeStatus(\'errMsg\')" title="Click to hide">&nbsp;</div>').show();
            $('<span>').html(data).appendTo('#errMsg');
            setTimeout(hideMsg, 3000);
            table.draw(false);
        }
    });
}

function changeHeight() {
    return $(window).height() * 0.4;
}

function buildTable() {
    scrollY = changeHeight();
    var table = $('#AIML').DataTable({
        processing: true,
        serverSide: true,
        paging: true,
        scrollX: true,
        scrollY: scrollY,
        //scrollCollapse: true,
        autoWidth: false,
        order: [1, 'asc'],
        ajax: 'editAJAX.php',
        columns: [
            {
                data: 'id',
                searchable: true,
                orderable: true,
                width: '15%',
                render: function (data, type, full, meta) {
                    return 'ID: ' + data + '<br><div class="deleteRow" onclick="deleteRow($(this))" title="Delete this row"><br>Delete</div>';
                }
            },
            {
                data: 'pattern',
                className: 'pattern editable',
                searchable: true,
                orderable: true,
                width: '15%',
                render: function (data, type, full, meta) {
                    return '<pre>' + data + '</pre>';
                }
            },
            {
                data: 'thatpattern',
                className: 'thatpattern editable',
                searchable: true,
                orderable: true,
                width: '15%',
                render: function (data, type, full, meta) {
                    return '<pre>' + data + '</pre>';
                }
            },
            {
                data: 'template',
                className: 'template editable',
                searchable: true,
                orderable: true,
                width: '25%',
                render: function (data, type, full, meta) {
                    return '<pre>' + data + '</pre>';
                }
            },
            {
                data: 'topic',
                className: 'topic editable',
                searchable: true,
                orderable: true,
                width: '15%',
                render: function (data, type, full, meta) {
                    return '<pre>' + data + '</pre>';
                }
            },
            {
                data: 'filename',
                className: 'filename editable',
                searchable: true,
                orderable: true,
                width: '15%'
            }
        ]
    });
    return table;
}
