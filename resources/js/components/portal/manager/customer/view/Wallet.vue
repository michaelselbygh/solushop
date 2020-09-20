<template>
    <div>
        <h5 style="text-align: right">Balance: <b style="color: green">GH¢ {{ balance(customer.milk.milk_value, customer.milkshake, customer.chocolate.chocolate_value) }}</b></h5>
        <div class="card">
            <transition name="fade" mode="out-in">
                <div  v-if="loading.wallet"  class="div-preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
            </transition>
            <div class="card-content collapse show">
                <div class="card-body card-dashboard">
                    <form>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Type</label>
                                    <select class="form-control" v-model="wallet.action" style='border-radius:7px;' required>
                                        <option>Pay-Out</option>
                                        <option>Pay-In</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" >
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input class="form-control round"  v-model="wallet.amount" type="number" step="0.01" required> 
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_description">Description</label>
                            <input class="form-control round" v-model="wallet.description" placeholder="Describe the payment." value="" type="text"> 
                        </div>
                        <div class="form-actions" style="text-align:center; padding: 0" >
                            <button @click.prevent="triggerCustomerPayment()"  class="btn btn-success">
                                    Record {{ wallet.action }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['customer', 'wallet', 'loading'],
    methods: {
        balance(milk, milkshake, chocolate){
            return Math.abs((milk * milkshake) - chocolate, 2).toFixed(2);
        },

        triggerCustomerPayment(){
            this.$emit("payCustomer", "");
        }
    }
}
</script>