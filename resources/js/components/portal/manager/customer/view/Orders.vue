<template>
    <div>
        <h5 class="card-title">{{ customer.first_name }}'s Orders</h5>
        <div class="card">
            <transition name="fade" mode="out-in">
                <div  v-if="loading.orders"  class="div-preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
            </transition>
            <div class="card-content collapse show">
                <div class="card-body card-dashboard">
                    <table ref="orders" class="table table-striped table-bordered zero-configuration" id="customer-orders">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Items Ordered</th>
                                <th>State</th>
                                <th>Made On</th>
                                <th>Updated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody> 
                            <tr v-for="order in customer.orders" :key="order.id">
                                <td>{{ order.id.substr(3) }}</td>
                                <td>
                                    <ul class="list-unstyled users-list m-0">
                                        <li v-for="item in order.order_items" v-bind:key="item.id" data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="item.oi_name" class="avatar avatar-sm pull-up">
                                            <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius"
                                            :src="'https://www.solushop.com.gh/app/assets/img/products/thumbnails/'+item.sku.product.images[0].pi_path+'.jpg'"
                                            :alt="item.oi_nam">
                                        </li>
                                    </ul>
                                </td>
                                <td v-html="order.order_state.os_user_html" class="text-truncate"></td>
                                <td>
                                    {{ order.created_at }}
                                </td>
                                <td>
                                    {{ order.updated_at }}
                                </td>
                                <td>
                                    <a target="new" :href="'https://www.solushop.com.gh/portal/manager/order/'+order.id">
                                        <button data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="View Order"  style="margin-top: 3px;" class="btn btn-info btn-sm round">
                                            <i class="ft-eye"></i>
                                        </button>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: ['customer', 'loading'],
    data(){
        return{
            
        }
    },
    mounted() {
        this.dt = $(this.$refs.orders).DataTable({
                    "order": [
                        [3, 'desc']
                    ],
                    "drawCallback": function() {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                });
        $('[data-toggle="tooltip"]').tooltip();
    },
    watch: {
        customer(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.orders).DataTable({
                    "order": [
                        [3, 'desc']
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