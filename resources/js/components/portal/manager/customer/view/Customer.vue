<template>
    <div>
        <transition name="fade" mode="out-in">
                <div  v-if="loading.main"  class="preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
        </transition>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-6">
                            <edit v-if="customer" @updateDetails="updateCustomerDetails"  :customer="customer" :loading="loading"></edit>
                            <orders v-if="customer" :customer="customer" :loading="loading"></orders>
                        </div>
                        <div class="col-md-5">
                            <wallet v-if="customer" @payCustomer="recordCustomerPayment"  :wallet="wallet" :customer="customer" :loading="loading"></wallet>
                            <addresses v-if="customer" :customer="customer" :loading="loading"></addresses>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
</template>

<script>
import Edit from './Edit.vue';
import Wallet from './Wallet.vue';
import Addresses from './Addresses.vue';
import Orders from './Orders.vue';


export default {
    components: {
        'edit' : Edit,
        'wallet' : Wallet,
        'addresses' : Addresses,
        'orders' : Orders
    },
    props: ['id'],
    data() {
        return{
            customer: null,
            loading:{
                main: true,
                details: false,
                wallet: false,
                orders: false,
                addresses: false
            },
            count: {
                updated: {
                    old: null,
                    new: null
                },
                orders: {
                    old: 0,
                    new: 0
                },
                addresses: {
                    old: 0,
                    new: 0
                },
            },
            wallet:{
                action: "Pay-In",
                amount: 0,
                description: ""

            },
            base_url: window.location.origin,
        }
    },
    async mounted(){
        await this.updateDetails();
        document.title = "Customer, " + this.customer.first_name+" "+this.customer.last_name;
        this.loading.main = false;
        setInterval(this.updateCounts, 10000);
    },
    methods: {
        async updateDetails(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/customer/records',
                                data: {
                                    id : this.id
                                }
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
            this.customer = response.data.records;
            this.setCounts(response.data);
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/customer/count',
                                data: {
                                    id : this.id
                                }
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

                            
            this.setCounts(response.data);

            //check change in customer data
            if (this.count.updated.new != this.count.updated.old) {
                this.loading.details = true;

                //update old counts
                this.count.updated.old = this.count.updated.new;
                var response = await this.getRecords();
                this.customer = response.data.records;
                this.loading.details = false;
            }

            //check change in customer orders
            if (this.count.orders.new != this.count.orders.old) {
                this.loading.orders = true;

                //update old counts
                this.count.orders.old = this.count.orders.new;

                var response = await this.getRecords();
                this.customer = response.data.records;
                this.loading.orders = false;
            }

            //check change in customer addresses
            if (this.count.addresses.new != this.count.addresses.old) {
                this.loading.addresses = true;

                //update old counts
                this.count.addresses.old = this.count.addresses.new;

                var response = await this.getRecords();
                this.customer = response.data.records;
                this.loading.addresses = false;
            }
        },
        
        setCounts(data){

            if(this.count.updated.old == null){
                //set old counts
                this.count.updated.old = data.updated;
                this.count.orders.old = data.orders;
                this.count.addresses.old = data.addresses;
            }

            //update new counts
            this.count.updated.new = data.updated;
            this.count.orders.new = data.orders;
            this.count.addresses.new = data.addresses;
        },

        getRecords(){
            return axios({
                        method: 'post',
                        url: 'https://www.solushop.com.gh/portal/manager/customer/records',
                        data: {
                            id : this.id
                        }
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
        },

        async updateCustomerDetails(){
            this.loading.details = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/customer/update-records',
                data: {
                    id : this.id,
                    first_name: this.customer.first_name,
                    last_name: this.customer.last_name,
                    email: this.customer.email,
                    phone: this.customer.phone
                }
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
            if (response.data.type == "error") {
                //sort 
                response.data.message.sort(function(a, b){
                    return a.length - b.length;
                });
                //show error message(s)
                for (let i = 0; i < response.data.message.length ; i++) {
                    this.$toast.error(response.data.message[i], "", {
                        timeout: 5000,
                    });
                }
            } else {
                //show success message
                this.$toast.success(response.data.message, "", {
                    timeout: 5000,
                });
            }
            this.count.updated.old = response.data.updated;
            this.loading.details = false;
        },

        async recordCustomerPayment(){
            this.loading.wallet = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/customer/record-payment',
                data: {
                    id : this.id,
                    type: this.wallet.action,
                    amount: this.wallet.amount,
                    description: this.wallet.description
                }
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
             if (response.data.type == "error") {
                //sort 
                response.data.message.sort();
                //show error message(s)
                for (let i = response.data.message.length - 1; i >= 0 ; i--) {
                    this.$toast.error(response.data.message[i], "", {
                        timeout: 5000,
                    });
                }
            } else {
                //show success message
                this.$toast.success(response.data.message, "", {
                    timeout: 5000,
                });

                this.count.updated.old = response.data.updated;
                this.customer.milkshake = response.data.milkshake;
                this.wallet.action = "Pay-In";
                this.wallet.amount = 0;
                this.wallet.description = "";
            }

            
            this.loading.wallet = false;
        }
    }
}
</script>