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
                        <div class="col-md-5">
                            <edit v-if="vendor" @updateDetails="updateVendorDetails"  :vendor="vendor" :loading="loading"></edit>
                        </div>
                        <div class="col-md-3">
                            <pay v-if="vendor" @payVendor="recordVendorPayment"  :pay="pay" :vendor="vendor" :loading="loading"></pay>
                            <!-- <sales v-if="vendor" :vendor="vendor" :loading="loading"></sales> -->
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <transactions v-if="vendor" :vendor="vendor" :loading="loading" :transactions="transactions"></transactions>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
import Sales from './Sales.vue'
import Edit from './Edit.vue';
import Pay from './Pay.vue';
import Transactions from './Transactions.vue';

export default {
    components: {
        'sales' : Sales,
        'edit' : Edit,
        'pay' : Pay,
        'transactions' : Transactions
    },
    props: ['id'],
    data() {
        return{
            vendor: null,
            transactions: null,
            loading:{
                main: true,
                details: false,
                pay: false,
                transactions: false
            },
            count: {
                updated: {
                    old: null,
                    new: null
                },
                transactions: {
                    old: 0,
                    new: 0
                }
            },
            pay:{
                amount: 0,
                type: "Pay-Out"
            },
            base_url: window.location.origin,
        }
    },
    async mounted(){
        await this.updateDetails();
        document.title = "Vendor, " + this.vendor.name;
        this.pay.amount = this.vendor.balance.toFixed(2);
        this.loading.main = false;
        setInterval(this.updateCounts, 10000);
    },
    methods: {
        async updateDetails(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/vendor/records',
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
            this.vendor = response.data.records;
            this.transactions = response.data.transactions;
            this.setCounts(response.data);
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/vendor/count',
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

            //check change in vendor data
            if (this.count.updated.new != this.count.updated.old) {
                this.loading.details = true;

                //update old counts
                this.count.updated.old = this.count.updated.new;
                var response = await this.getRecords();
                this.vendor = response.data.records;
                this.loading.details = false;
            }

            //check change in vendor transactions
            if (this.count.transactions.new != this.count.transactions.old) {
                this.loading.transactions = true;

                //update old counts
                this.count.transactions.old = this.count.transactions.new;

                var response = await this.getRecords();
                this.transactions = response.data.transactions;
                this.loading.transactions = false;
            }

        },
        
        setCounts(data){

            if(this.count.updated.old == null){
                //set old counts
                this.count.updated.old = data.count.updated;
                this.count.transactions.old = data.count.transactions;
            }

            //update new counts
            this.count.updated.new = data.count.updated;
            this.count.transactions.new = data.count.transactions;
        },

        getRecords(){
            return axios({
                        method: 'post',
                        url: 'https://www.solushop.com.gh/portal/manager/vendor/records',
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

        async updateVendorDetails(){
            this.loading.details = true;
            let formData = new FormData();
            formData.append('id', this.id);
            formData.append('name', this.vendor.name);
            formData.append('email', this.vendor.email);
            formData.append('phone', this.vendor.phone);
            formData.append('alt_phone', this.vendor.alt_phone);
            formData.append('address', this.vendor.address);
            formData.append('mode_of_payment', this.vendor.mode_of_payment);
            formData.append('payment_details', this.vendor.payment_details);
            if (this.vendor.header != null && this.vendor.header != '') {
                formData.append('header', this.vendor.header);
            }
            

            var response = await axios.post('https://www.solushop.com.gh/portal/manager/vendor/update-records', formData, {
                headers: {
                'Content-Type': 'multipart/form-data'
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
                this.vendor = response.data.records;
            }
            this.loading.details = false;
        },

        async recordVendorPayment(){
            this.loading.pay = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/vendor/record-payment',
                data: {
                    id : this.id,
                    amount: this.pay.amount,
                    type: this.pay.type
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

                this.vendor = response.data.records;
                this.count.updated.old = response.data.count.updated;
                this.pay.amount = this.vendor.balance.toFixed(2);
                
            }

            this.loading.pay = false;
        }

        

    }
}
</script>