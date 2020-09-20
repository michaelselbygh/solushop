<template>
    <div>
        <transition name="fade" mode="out-in">
            <div  v-if="loading"  class="preloader-wrap">
                <div class=" custom-center">
                    <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                </div>
            </div>
        </transition>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Generate Coupon</h5>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card" style="padding:10px;">
                                <h5 class="card-title" style="color:red">Note</h5>
                                <div class="card-content collapse show">
                                    <div class="card-body" style="padding-top: 5px;">
                                        <ol style="padding-left:0px;">
                                            <li>Generating a coupon without Management concern is criminal and will result in immediate dismissal from Solushop. Criminal prosecution may be considered in addition.</li>
                                            <li>Use this feature ONLY when you are instructed to do so.</li>
                                            <li>All activity on Solushop Ghana is recorded.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="card" style="">
                                <div class="card-content collapse show">
                                    <div class="card-body">
                                        <div class="form-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="value">Value</label>
                                                        <input v-model="value" class="form-control round" min="1" value="1" type="number" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="expiry_date">Expiry Date</label>
                                                        <input v-model="expiry" :min="today" type="date" class="form-control round" required> 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-actions" style="text-align:center; padding: 20px;">
                                                <a href="https://www.solushop.com.gh/portal/manager/coupons">
                                                    <button type="button" class="btn btn-danger mr-1" >
                                                            Back To Coupons
                                                    </button>
                                                </a>
                                                <button @click="generate()" class="btn btn-success">
                                                        Generate Coupon
                                                </button>
                                            </div>
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
            loading: true,
            value: 1,
            expiry: this.today,
            base_url: window.location.origin,
        }
    },

    mounted() {
        this.loading = false;
    },

    methods: {
        async generate(){
            this.loading = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/coupon/generate',
                data: {
                    expiry: this.expiry,
                    value: this.value
                }
            }).catch(error => {
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
            this.loading = false; 
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
        }
    },
    
    computed: {
        today(){
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
            var yyyy = today.getFullYear();

            today = yyyy + '-' + mm + '-' + dd;
            return today;
        }
    }
}
</script>