define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'spaceconfig/space/index' + location.search,
                    add_url: 'spaceconfig/space/add',
                    edit_url: 'spaceconfig/space/edit',
                    del_url: 'spaceconfig/space/del',
                    multi_url: 'spaceconfig/space/multi',
                    import_url: 'spaceconfig/space/import',
                    table: 'space',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible:true,
                //启用固定列
                fixedColumns: true,
                //固定右侧列数
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'space_type', title: __('Space_type'),searchList: {"car":__('Space_type car'),"ai_car":__('Space_type ai_car')}, formatter: Table.api.formatter.label},
                        {field: 'self_type', title: __('Self_type'),searchList: {"self":__('Self_type self'),"ai_car":__('Self_type not_self')}, formatter: Table.api.formatter.label},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE',},
                        {field: 'space_name', title: __('Space_name'), operate: 'LIKE'},
                        {field: 'curator_name', title: __('Curator_name'), operate: 'LIKE',},
                        {field: 'space_phone', title: __('Space_phone'), operate: false},
                        {field: 'subject_type', title: __('Subject_type'), searchList: {"subject1":__('Subject_type subject1'),"subject2":__('Subject_type subject2'),"subject3":__('Subject_type subject3'),"subject4":__('Subject_type subject4')}, operate:false, formatter: Table.api.formatter.label},
                        {field: 'car_type', title: __('Car_type'), searchList: {"cartype1":__('Car_type cartype1'),"cartype2":__('Car_type cartype2')},operate:false, formatter: Table.api.formatter.label},
                        {field: 'starttimes', title: __('Starttimes'),operate:false},
                        {field: 'endtimes', title: __('Endtimes'),operate:false},
                        {field: 'day', title: __('Day'),operate:false},
                        {field: 'advance_cancel_times', title: __('Advance_cancel_times'),operate:false},
                        {field: 'order_length', title: __('Order_length'),operate:false},
                        {field: 'pay_status', title: __('Pay_status'), searchList: {"0":__('Pay_status 0'),"1":__('Pay_status 1')}, formatter: Table.api.formatter.status,operate:false},
                        {field: 'pick_up_status', title: __('Pick_up_status'), searchList: {"0":__('Pick_up_status 0'),"1":__('Pick_up_status 1')}, formatter: Table.api.formatter.status,operate:false},
                        {field: 'city', title: __('City'), operate: false},
                        {field: 'address', title: __('Address'), operate: false},
                        {field: 'lng', title: __('Lng'), operate:false},
                        {field: 'lat', title: __('Lat'), operate:false},
                        {field: 'region_info', title: __('Region_info'), operate: false},
                        {field: 'area', title: __('Area'), operate:false},
                        {field: 'regionimage', title: __('Regionimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'images', title: __('Images'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'space_state', title: __('Space_state'), searchList: {"yes":__('Space_state yes'),"no":__('Space_state no')}, formatter: Table.api.formatter.normal},
                        {field: 'praise', title: __('Praise'),operate:false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});