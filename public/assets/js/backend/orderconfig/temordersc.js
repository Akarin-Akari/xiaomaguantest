define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'orderconfig/temordersc/index' + location.search,
                    add_url: 'orderconfig/temordersc/add',
                    edit_url: 'orderconfig/temordersc/edit',
                    del_url: 'orderconfig/temordersc/del',
                    multi_url: 'orderconfig/temordersc/multi',
                    import_url: 'orderconfig/temordersc/import',
                    table: 'temporary_order_sc',
                }
            });
            Date.prototype.Format = function (fmt) { // author: meizz
                var o = {
                    "M+": this.getMonth() + 1, // 月份
                    "d+": this.getDate(), // 日
                    "h+": this.getHours(), // 小时
                    "m+": this.getMinutes(), // 分
                    "s+": this.getSeconds(), // 秒
                    "q+": Math.floor((this.getMonth() + 3) / 3), // 季度
                    "S": this.getMilliseconds() // 毫秒
                };
                if (/(y+)/.test(fmt))
                    fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
                for (var k in o)
                    if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
                        return fmt;
            }
            var time1 = new Date().Format("yyyy-MM-dd 00:00:00");
            var time2 = new Date().Format("yyyy-MM-dd 23:59:59");
            var time = time1 + ' - '+ time2;

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
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'ordernumber', title: __('Ordernumber'), operate: 'LIKE'},
                        {field: 'stu_id', title: __('Stu_id'), operate: 'LIKE'},
                        {field: 'intentstudent.phone', title: __('Intentstudent.phone')},
                        {field: 'coachsc.name', title: __('Coachsc.name'), operate: 'LIKE'},
                        {field: 'car.machine_code', title: __('Car.machine_code'), operate: 'LIKE'},
                        {field: 'machineai.machine_code', title: __('Machineai.machine_code'), operate: 'LIKE'},
                        {field: 'car.car_type', title: __('Car.car_type'), operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'reserve_starttime', title: __('Reserve_starttime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime,defaultValue:time},
                        {field: 'reserve_endtime', title: __('Reserve_endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'starttime', title: __('Starttime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'endtime', title: __('Endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'order_status', title: __('Order_status'), searchList: {"unpaid":__('Order_status unpaid'),"paid":__('Order_status paid'),"accept_unexecut":__('Order_status accept_unexecut'),"executing":__('Order_status executing'),"finished":__('Order_status finished'),"cancel_unrefund":__('Order_status cancel_unrefund'),"cancel_refunded":__('Order_status cancel_refunded')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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