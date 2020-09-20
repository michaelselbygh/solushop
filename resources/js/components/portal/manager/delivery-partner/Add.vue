<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-5">
                            <h5 class="card-title">
                                Add Delivery Partner
                            </h5>
                            <div class="card" style="">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body">
                                        <div class="form-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="first_name">First Name</label>
                                                        <input class="form-control round" placeholder="Enter first name" v-model="partner.first_name" type="text">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="last_name">Last Name</label>
                                                        <input class="form-control round" placeholder="Enter last name" v-model="partner.last_name"  type="text"> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="dp_company">Company</label>
                                                        <input class="form-control round" placeholder="Enter company" v-model="partner.dp_company"  type="text">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="email">Email</label>
                                                        <input class="form-control round" placeholder="Enter email" v-model="partner.email"  type="email">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="payment_details">Payment Details</label>
                                                <input class="form-control round" placeholder="Enter payment details" v-model="partner.payment_details" type="text">
                                            </div>
                                        </div>
                                        <div class="form-actions" style="text-align:center; padding: 0px;">
                                            <button @click="add()" class="btn btn-success">
                                                    Add Associate {{ partner.first_name }}
                                            </button>
                                        </div>
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
            partner: {
                first_name: null,
                last_name: null,
                email: null,
                dp_company: null,
                payment_details: null
            },

            loading: true
        }
    },
    mounted(){
        this.loading = false
    },
    methods: {
        async add(){
            this.loading = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/delivery-partners/add',
                data: {
                    first_name: this.partner.first_name,
                    last_name: this.partner.last_name,
                    email: this.partner.email,
                    dp_company: this.partner.dp_company,
                    payment_details: this.partner.payment_details,
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

                this.partner.first_name = "";
                this.partner.last_name = "";
                this.partner.email = "";
                this.partner.dp_company = "";
                this.partner.payment_details = "";
                
            }

            this.loading = false;
        }
    }
}
</script>