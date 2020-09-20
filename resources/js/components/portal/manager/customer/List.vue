<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Customers</h5>
                            <div class="card" style="min-height: 615px;">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body card-dashboard">
                                        <table ref="customers" id="customer-records" class="table table-striped table-bordered zero-configuration">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Phone</th>
                                                    <th>Email</th>
                                                    <th>Balance</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody> 
                                                    <tr v-for="customer in customers" :key="customer.id"> 
                                                        <td>{{ customer.id }}</td>
                                                        <td>{{ customer.first_name+" "+customer.last_name }}</td>
                                                        <td>{{ "0"+customer.phone.substr(3) }}</td>
                                                        <td>{{ customer.email }}</td>
                                                        <td><b style="color: green">{{ balance(customer.milk.milk_value, customer.milkshake, customer.chocolate.chocolate_value) }}</b></td>
                                                        <td>
                                                            <a :href="'https://www.solushop.com.gh/portal/manager/customer/'+customer.id">
                                                                <button  data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="'View '+customer.first_name+' '+customer.last_name" style="margin-top: 3px;" class="btn btn-info btn-sm round">
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
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data(){
        return {
            customers: null,
            loading: true,
            count: {
                old: 0,
                new: 0
            },
            dt: null,
            base_url: window.location.origin,
        }
    },

    async mounted(){
        this.dt = $(this.$refs.customers).DataTable();
        await this.updateRecords();
        this.loading = false;
        setInterval(this.updateCounts, 10000);
    },


    methods: {
        balance(milk, milkshake, chocolate){
            return Math.abs((milk * milkshake) - chocolate, 2).toFixed(2);
        },

        setCount(count){
            if (this.count.old == 0) {
                this.count.old = count;
            }
            this.count.new = count;
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/customers/count',
                            })
                            .catch(error => {
                                switch (error.response.status) {
                                    case 401:
                                        this.$toast.question("User logged out. Kindly log back in.", '', {
                                            timeout: 3000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                            }
                                        });
                                        break;

                                    case 419:
                                        this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                            timeout: 10000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                document.location.reload();
                                            }
                                        });
                                        

                                        break;
                                
                                    default:
                                        break;
                                }
                            });

            
            this.setCount(response.data.count);
            if (JSON.stringify(this.count.old) != JSON.stringify(this.count.new)) {
                this.loading = true;

                //update old counts
                this.count.old = this.count.new;

                await this.updateRecords();
                this.loading = false;
            }
        },

        async updateRecords(){
            var recordsResponse = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/customers/records',
            })
            .catch(error => {
                switch (error.response.status) {
                    case 401:
                        this.$toast.question("User logged out. Kindly log back in.", '', {
                            timeout: 3000,
                            close: false,
                            overlay: true,
                            displayMode: 'once',
                            id: 'question',
                            zindex: 999,
                            position: 'center',
                            onClosing: function(instance, toast, closedBy){
                                window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                            }
                        });
                        break;

                    case 419:
                        this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                            timeout: 10000,
                            close: false,
                            overlay: true,
                            displayMode: 'once',
                            id: 'question',
                            zindex: 999,
                            position: 'center',
                            onClosing: function(instance, toast, closedBy){
                                document.location.reload();
                            }
                        });
                        

                        break;
                
                    default:
                        break;
                }
            });
            this.customers = recordsResponse.data.records;
            this.setCount(recordsResponse.data.count);
        },
    },
    
    watch: {
        customers(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.customers).DataTable({
                    "order": [
                        [1, 'asc']
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