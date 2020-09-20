<template>
    <div>
        <h5 class="card-title">{{ customer.first_name  }}'s Addresses</h5>
        <div class="card">
            <transition name="fade" mode="out-in">
                <div  v-if="loading.addresses"  class="div-preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
            </transition>
            <div class="card-content collapse show">
                <div class="card-body card-dashboard">
                    <table ref="addresses" class="table table-striped table-bordered zero-configuration">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody> 
                            <tr v-for="address in customer.addresses" :key="address.id">
                                <td>{{ address.id }} </td>
                                <td>{{ address.ca_town+" "+address.ca_address }} <i class="la la-check" style="color: green; font-weight: 900" v-if="customer.default_address == address.id"></i></td>
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
            records: this.customer.addresses
        }
    },
    mounted() {
        this.dt = $(this.$refs.addresses).DataTable({
                    "order": [
                        [1, 'asc']
                    ],
                    "drawCallback": function() {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                });
        $('[data-toggle="tooltip"]').tooltip();
    }
}
</script>