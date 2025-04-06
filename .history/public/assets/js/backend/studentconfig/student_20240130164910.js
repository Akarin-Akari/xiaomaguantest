define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'studentconfig/student/index' + location.search,
                    add_url: 'studentconfig/student/add',
                    edit_url: 'studentconfig/student/edit',
                    del_url: 'studentconfig/student/del',
                    multi_url: 'studentconfig/student/multi',
                    import_url: 'studentconfig/student/import',
                    export_url: 'studentconfig/student/export',
                    createcontract_url: 'studentconfig/student/createcontract',
                    table: 'student',
                }
            });

            var table = $("#table");

            $(document).on("click", ".btn-export", function () {
                var ids = Table.api.selectedids(table);
                var page = table.bootstrapTable('getData');
                var all = table.bootstrapTable('getOptions').totalRows;
                Layer.confirm("请选择导出的选项<form action='" + Fast.api.fixurl("studentconfig/student/detail1") + "' method='post'><input type='hidden' name='ids' value='' /><input type='hidden' name='filter' ><input type='hidden' name='op'><input type='hidden' name='search'><input type='hidden' name='columns'></form>", {
                    title: '导出数据',
                    btn: ["全部(" + all + "条)"],
                    success: function (layero, index) {
                        $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                    }
                    , yes: function (index, layero) {
                        submitForm("all", layero);
                        console.log(index, layero)
                    }
                })
            });
            var submitForm = function (ids, layero) {
                var options = table.bootstrapTable('getOptions');
                console.log(options);
                var columns = [];
                $.each(options.columns[0], function (i, j) {
                    if (j.field && !j.checkbox && j.visible && j.field != 'operate') {
                        columns.push(j.field);
                    }
                });
                var search = options.queryParams({});
                $("input[name=search]", layero).val(options.searchText);
                $("input[name=ids]", layero).val(ids);
                $("input[name=filter]", layero).val(search.filter);
                $("input[name=op]", layero).val(search.op);
                $("input[name=columns]", layero).val(columns.join(','));
                $("form", layero).submit();
            };
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                // $(".btn-editone,.btn-add").data("area", ["100%", "100%"]);
                $(".btn-add").data("area", ["100%", "100%"]);
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                //启用固定列
                fixedColumns: true,
                //固定右侧列数
                fixedRightNumber: 1,
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'stu_id', title: __('Stu_id') },
                        { field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE' },
                        { field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE' },
                        { field: 'name', title: __('Name'), operate: 'LIKE' },
                        { field: 'nation', title: __('Nation'), operate: 'LIKE' },
                        // {field: 'vx_name', title: __('Vx_name'),operate:false,},
                        { field: 'phone', title: __('Phone'), operate: 'LIKE' },
                        { field: 'car_type', title: __('Car_type'), searchList: { "cartype1": __('Car_type cartype1'), "cartype2": __('Car_type cartype2'), "cartype3": __('Car_type cartype3'), "cartype4": __('Car_type cartype4'), "cartype5": __('Car_type cartype5'), "cartype6": __('Car_type cartype6'), "cartype7": __('Car_type cartype7'), "cartype8": __('Car_type cartype8') }, operate: 'FIND_IN_SET', formatter: Table.api.formatter.label },
                        // {field: 'regis_type', title: __('regis_type') , searchList: $.getJSON("studentconfig/student/registypeList")},
                        // {field: 'payment_process', title: __('payment_process') , searchList: $.getJSON("studentconfig/student/paymentprocessList")},
                        // {field: 'payment_process', title: __('payment_process') , searchList: {"unpaid":__('Payment_process unpaid'),"paying":__('Payment_process paying'),"payed":__('Payment_process payed')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        // {field: 'signupsource.sign_up_source_name', title: __('Signupsource.sign_up_source_name')},
                        // {field: 'regislx.type', title: __('regislx.type')},
                        { field: 'courselog.course', title: __('CourseLog.course') },
                        { field: 'subject_type', title: __('Subject_type'), searchList: { "subject1": __('Subject_type subject1'), "subject2": __('Subject_type subject2'), "subject3": __('Subject_type subject3'), "subject4": __('Subject_type subject4') }, operate: 'FIND_IN_SET', formatter: Table.api.formatter.label },
                        { field: 'registtime', title: __('Registtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'recommender.name', title: __('Recommender.name'), operate: false },
                        { field: 'introducer', title: __('Introducer'), operate: 'LIKE' },
                        { field: 'contract_state', title: __('Contract_state'), searchList: { "0": __('Contract_state 0'), "1": __('Contract_state 1') }, operate: 'FIND_IN_SET', formatter: Table.api.formatter.label },
                        { field: 'order_num', title: __('Order_num'), operate: 'LIKE' },

                        // {field: 'study_sign', title: __('Study_sign'), searchList: {"studying":__('Study_sign studying'),"graduation":__('Study_sign graduation'),"expired":__('Study_sign expired'),"drop_out":__('Study_sign drop_out'),"transfer":__('Study_sign transfer')}, formatter: Table.api.formatter.normal},
                        // {field: 'study_process', title: __('Study_process'), searchList: {"process1":__('Study_process process1'),"process2":__('Study_process process2'),"process3":__('Study_process process3'),"process4":__('Study_process process4'),"process5":__('Study_process process5'),"process6":__('Study_process process6'),"process7":__('Study_process process7'),"process8":__('Study_process process8'),"process9":__('Study_process process9'),"process10":__('Study_process process10'),"process11":__('Study_process process11'),"process12":__('Study_process process12'),"process13":__('Study_process process13'),"process14":__('Study_process process14')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        // {field: 'regis_money', title: __('Regis_money')},
                        // {field: 'recommender.name', title: __('Recommender.name')},
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'updatetime', title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    title: '生成合同',
                                    text: '生成合同',
                                    classname: 'btn btn-xs btn-success createcontract btn-dialog',
                                    url: 'studentconfig/student/createcontract',
                                    hidden: function (rows) {
                                        if (rows.contract_path) {
                                            return true;
                                        }
                                    },

                                },
                                // {
                                //     name: 'audit',
                                //     text: '模拟器已缴费',
                                //     title: __('已审核'),
                                //     classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                //     // confirm: '是否审核通过',
                                //     url: 'bill/installment/audit',
                                //     hidden:function(rows){
                                //         console.log(rows.cooperation_id)

                                //         if(rows.pay_cooperation == null){
                                //             return true;
                                //         }
                                //     },
                                //     disable:function(rows){
                                //         if(rows.audit == 'yes'){
                                //             return true;
                                //         }
                                //     }

                                // }
                            ],
                            formatter: Table.api.formatter.operate
                        }
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
        export: function () {
            Controller.api.bindevent();
        },
        detail: function () {
            // var fundsclass = $("#fundsclass").find("option:selected").attr("data-state");
            // console.log(fundsclass)
            // $("#fundsclass").change(function(){
            //     //当前选中的值
            //     console.log($(this));
            //     //当前选中的自定义属性 ‘data-price’
            //     console.log($(this).find("option:selected").attr('data-state'));
            // });
        },
        createcontract: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent("form[role=form]", null, null, function (success, error) {
                    var that = this;
                    // console.log(222)
                    // Layer.confirm("确认提交？", function(){
                    //     Form.api.submit(that, success, error);
                    // });
                });
            }

        }

    };
    return Controller;
});