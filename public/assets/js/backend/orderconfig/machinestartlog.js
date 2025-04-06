define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'orderconfig/machinestartlog/index' + location.search,
                    // add_url: 'orderconfig/machinestartlog/add',
                    // edit_url: 'orderconfig/machinestartlog/edit',
                    // del_url: 'orderconfig/machinestartlog/del',
                    multi_url: 'orderconfig/machinestartlog/multi',
                    import_url: 'orderconfig/machinestartlog/import',
                    table: 'order_log',
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
                
                visible: false,
                showToggle: false,
                showColumns: false,
                search:false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'ordernumber', title: __('Ordernumber'), operate: 'LIKE'},
                        {field: 'start_or_end', title: __('Start_or_end'), searchList: {"1":__('Start_or_end 1'),"2":__('Start_or_end 2')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime,defaultValue:time},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        // add: function () {
        //     Controller.api.bindevent();
        // },
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});