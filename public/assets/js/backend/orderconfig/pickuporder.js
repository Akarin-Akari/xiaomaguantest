define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'orderconfig/pickuporder/index' + location.search,
                    add_url: 'orderconfig/pickuporder/add',
                    edit_url: 'orderconfig/pickuporder/edit',
                    del_url: 'orderconfig/pickuporder/del',
                    multi_url: 'orderconfig/pickuporder/multi',
                    import_url: 'orderconfig/pickuporder/import',
                    table: 'pickup_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'ordernumber', title: __('Ordernumber'), operate: 'LIKE'},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'stu_id', title: __('Stu_id'), operate: 'LIKE'},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        {field: 'student.phone', title: __('Student.phone')},
                        {field: 'pickupcar.machine_code', title: __('Car.machine_code'), operate: 'LIKE'},
                        {field: 'reserve_starttime', title: __('Reserve_starttime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'reserve_endtime', title: __('Reserve_endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'pickup_time', title: __('Pickup_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'payModel', title: __('Paymodel'), searchList: {"1":__('Paymodel 1'),"2":__('Paymodel 2')}, formatter: Table.api.formatter.normal},
                        {field: 'order_status', title: __('Order_status'), searchList: {"unpaid":__('Order_status unpaid'),"paid":__('Order_status paid'),"accept_unexecut":__('Order_status accept_unexecut'),"executing":__('Order_status executing'),"finished":__('Order_status finished'),"cancel_unrefund":__('Order_status cancel_unrefund'),"cancel_refunded":__('Order_status cancel_refunded')}, formatter: Table.api.formatter.status},
                        {field: 'ordertype', title: __('Ordertype'), searchList: {"1":__('Ordertype 1'),"2":__('Ordertype 2'),"3":__('Ordertype 3')}, formatter: Table.api.formatter.normal},
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
