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
                            <edit v-if="partner" @updateDetails="updatePartnerDetails"  :partner="partner" :loading="loading"></edit>
                        </div>
                        <div class="col-md-3">
                            <pay v-if="partner" @payPartner="recordPartnerPayment"  :pay="pay" :partner="partner" :loading="loading"></pay>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <transactions v-if="partner" :partner="partner" :loading="loading" :transactions="transactions"></transactions>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
import Edit from './Edit.vue';
import Pay from './Pay.vue';
import Transactions from './Transactions.vue';

export default {
    components: {
        'edit' : Edit,
        'pay' : Pay,
        'transactions' : Transactions
    },
    props: ['id'],
    data() {
        return{
            partner: null,
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
                amount: 0
            },
            base_url: window.location.origin,
        }
    },
    async mounted(){
        await this.updateDetails();
        document.title = "Partner " + this.partner.first_name + " " + this.partner.last_name;
        this.pay.amount = this.partner.balance.toFixed(2);
        this.loading.main = false;
        setInterval(this.updateCounts, 10000);
    },
    methods: {
        async updateDetails(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/delivery-partner/records',
                                data: {
                                    id : this.id
                                }
                            });
            this.partner = response.data.records;
            this.transactions = response.data.transactions;
            this.setCounts(response.data);
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/delivery-partner/count',
                                data: {
                                    id : this.id
                                }
                            });
                            
            this.setCounts(response.data);

            //check change in partner data
            if (this.count.updated.new != this.count.updated.old) {
                this.loading.details = true;

                //update old counts
                this.count.updated.old = this.count.updated.new;
                var response = await this.getRecords();
                this.partner = response.data.records;
                this.loading.details = false;
            }

            //check change in partner transactions
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
                        url: 'https://www.solushop.com.gh/portal/manager/delivery-partner/records',
                        data: {
                            id : this.id
                        }
                    });
        },

        async updatePartnerDetails(){
            this.loading.details = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/delivery-partner/update-records',
                data: {
                    id : this.id,
                    first_name: this.partner.first_name,
                    last_name: this.partner.last_name,
                    dp_company: this.partner.dp_company,
                    email: this.partner.email,
                    payment_details: this.partner.payment_details
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
            }
            this.count.updated.old = response.data.updated;
            this.loading.details = false;
        },

        async recordPartnerPayment(){
            this.loading.pay = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/delivery-partner/record-payment',
                data: {
                    id : this.id,
                    amount: this.pay.amount
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

                this.partner = response.data.records;
                this.count.updated.old = response.data.count.updated;
                this.pay.amount = this.partner.balance.toFixed(2);
                
            }

            this.loading.pay = false;
        }

    }
}
</script>