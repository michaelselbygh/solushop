<template>
    <div class="table-responsive">
        <table ref="orders" id="order-records" class="table table-striped table-bordered zero-configuration">
            <thead>
                <tr>
                    <th class="border-top-0">Customer Name</th>
                    <th class="border-top-0">Customer Phone</th>
                    <th class="border-top-0">Products Purchased</th>
                    <th class="border-top-0">Order ID</th>
                    <th class="border-top-0">State</th>
                    <th class="border-top-0">Made On</th>
                    <th class="border-top-0">Last Updated</th>
                    <th class="border-top-0">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="record in records" v-bind:key="record.id">
                    <td class="text-truncate">
                        <span>
                            {{  record.customer.first_name+" "+record.customer.last_name }}
                        </span>
                    </td>
                    <td class="text-truncate">
                        <span>
                            {{ "0"+record.customer.phone.substr(3) }}
                        </span>
                    </td>
                    <td class="text-truncate p-1">
                        <ul class="list-unstyled users-list m-0">
                            <li v-for="item in record.order_items" v-bind:key="item.id" data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="item.oi_name" class="avatar avatar-sm pull-up">
                                <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius"
                                :src="'https://www.solushop.com.gh/app/assets/img/products/thumbnails/'+item.sku.product.images[0].pi_path+'.jpg'"
                                :alt="item.oi_nam">
                            </li>
                        </ul>
                    </td>
                    <td class="text-truncate">
                        {{ record.id }}
                    </td>
                    <td v-html="record.order_state.os_user_html" class="text-truncate"></td>
                    <td class="text-truncate">
                        {{ record.order_date }}
                    </td>
                    <td class="text-truncate">
                        {{ record.updated_at }}
                    </td>
                    <td  class="text-truncate">
                        <a target="new" :href="'https://www.solushop.com.gh/portal/manager/order/'+record.id">
                            <button data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="View Order"  style="margin-top: 3px;" class="btn btn-info btn-sm round">
                                <i class="ft-eye"></i>
                            </button>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>     
</template>

<script>
export default {
    props: ['records'],
    mounted() {
        this.dt = $(this.$refs.orders).DataTable();
    },
    watch: {
        records(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.orders).DataTable({
                    "order": [
                        [5, 'desc']
                    ],
                    "drawCallback": function() {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                });
                $('[data-toggle="tooltip"]').tooltip();
            });
        }
    }
}



</script>