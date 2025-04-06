define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'coachconfig/coachsc/index' + location.search,
                    add_url: 'coachconfig/coachsc/add',
                    edit_url: 'coachconfig/coachsc/edit',
                    del_url: 'coachconfig/coachsc/del',
                    multi_url: 'coachconfig/coachsc/multi',
                    import_url: 'coachconfig/coachsc/import',
                    table: 'coach_cs',
                }
            });

            var table = $("#table");

            // 初始化表格;
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'coach_id', title: __('Coach_id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'phone', title: __('Phone'), operate: 'LIKE'},
                        {field: 'sex', title: __('Sex'), searchList: {"male":__('Sex male'),"female":__('Sex female')}, formatter: Table.api.formatter.normal},
                        {field: 'car_type', title: __('Car_type'), searchList: {"cartype1":__('Car_type cartype1'),"cartype2":__('Car_type cartype2')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'subject_type', title: __('Subject_type'), searchList: {"subject1":__('Subject_type subject1'),"subject2":__('Subject_type subject2'),"subject3":__('Subject_type subject3'),"subject4":__('Subject_type subject4')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'photoimage', title: __('Photoimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'idcard1image', title: __('Idcard1image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'idcard2image', title: __('Idcard2image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'drilicenceimage', title: __('Drilicenceimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'fstdrilictime', title: __('Fstdrilictime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'succtime', title: __('Succtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'failuretime', title: __('Failuretime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'bank_num', title: __('Bank_num'), operate: 'LIKE'},
                        {field: 'opening_bank', title: __('Opening_bank'), operate: 'LIKE'},
                        {field: 'security_card', title: __('Security_card'), operate: 'LIKE'},
                        {field: 'hiretime', title: __('Hiretime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'leavetime', title: __('Leavetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'teach_state', title: __('Teach_state'), searchList: {"yes":__('Teach_state yes'),"no":__('Teach_state no')}, formatter: Table.api.formatter.normal},
                        {field: 'praise', title: __('Praise')},
                        {field: 'coach_remark', title: __('Coach_remark'), operate: 'LIKE'},
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