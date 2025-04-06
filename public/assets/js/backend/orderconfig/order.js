
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'orderconfig/order/index' + location.search,
                    add_url: 'orderconfig/order/add',
                    edit_url: 'orderconfig/order/edit',
                    del_url: 'orderconfig/order/del',
                    multi_url: 'orderconfig/order/multi',
                    import_url: 'orderconfig/order/import',
                    table: 'order',
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
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE',
                            formatter: function (value, row, index) {
                                if(!row.student) return '-';
                                value='<a href="javascript:void(0);" data-url="studentconfig/student/edit/ids/' + row.student.id
                                    +'" data-area="[&quot;100%&quot;,&quot;100%&quot;]" class="btn-dialog" data-title="'+ row.student.name+'">'+row.student.name+'</a>';
                                return value;
                            },
                        },
                        {field: 'student.phone', title: __('Student.phone')},
                        {field: 'coach.name', title: __('Coach.name'), operate: 'LIKE'},
                        {field: 'car_type', title: __('Car_type'), searchList: {"cartype1":__('Car_type cartype1'),"cartype2":__('Car_type cartype2')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'subject_type', title: __('Subject_type'), searchList: {"subject1":__('Subject_type subject1'),"subject2":__('Subject_type subject2'),"subject3":__('Subject_type subject3'),"subject4":__('Subject_type subject4')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'machinecar.machine_code', title: __('Machinecar.machine_code'), operate: 'LIKE'},
                        {field: 'level', title: __('Level'), operate: 'LIKE'},
                        {field: 'reserve_starttime', title: __('Reserve_starttime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime,defaultValue:time},
                        {field: 'reserve_endtime', title: __('Reserve_endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'starttime', title: __('Starttime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'endtime', title: __('Endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'payModel', title: __('Paymodel'), searchList: {"1":__('Paymodel 1'),"2":__('Paymodel 2')}, formatter: Table.api.formatter.normal},
                        {field: 'order_status', title: __('Order_status'), searchList: {"unpaid":__('Order_status unpaid'),"paid":__('Order_status paid'),"accept_unexecut":__('Order_status accept_unexecut'),"executing":__('Order_status executing'),"finished":__('Order_status finished'),"cancel_unrefund":__('Order_status cancel_unrefund'),"cancel_refunded":__('Order_status cancel_refunded')}, formatter: Table.api.formatter.status},
                        // {field: 'coach_boot_type', title: __('Coach_boot_type'), searchList: {"0":__('Coach_boot_type 0'),"1":__('Coach_boot_type 1')}, formatter: Table.api.formatter.normal},
                        // {field: 'student_boot_type', title: __('Student_boot_type'), searchList: {"0":__('Student_boot_type 0'),"1":__('Student_boot_type 1')}, formatter: Table.api.formatter.normal},
                        // {field: 'evaluation', title: __('Evaluation'), searchList: {"0":__('Evaluation 0'),"1":__('Evaluation 1')}, formatter: Table.api.formatter.normal},
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