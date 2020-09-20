
var portalLogin = new Vue({
    el: "#portal-login-section",
    data: {
        form:{
            username: "" ,
            password: "" ,
            remember: false
        },
        entity: entity,
        logoUrl: '/portal/images/logo/logo.png',
        loaderUrl: '/app/assets/img/loader.gif',
        loading: false
    },
    computed: {
        
        submitUrl(){
            return '/portal/'+this.entitySlug+'/login';
        },

        redirectUrl(){
            return '/portal/'+this.entitySlug;
        },

        entitySlug(){
            if (this.entity.search(" ")){
                return this.entity.split(" ").join("-").toLowerCase();
            }else{
                return this.entity.toLowerCase();
            }
        }

    },
    methods: {
        login(){
            this.loading = true;
            if(!this.form.username || !this.form.password){
                this.loading = false;
                this.$toast.error("Incomplete Credentials", "", {
                    timeout: 5000,
                });
            }else{
                //validate user with db
                axios({
                    method: 'post',
                    url: this.submitUrl,
                    data: this.form,
                 })
                .then(response => { 
                    if(response.data.valid == true){
                        this.$toast.success(response.data.message, "", {
                            timeout: 2000,
                        });

                        setTimeout(() => {
                            window.location.href = this.redirectUrl;
                        }, 2000);
                    }else{
                        this.loading = false;
                        this.$toast.error(response.data.message, "", {
                            timeout: 5000,
                        });
                    }

                    
                })
                .catch(error => {
                    if(error.response.status == 419){
                        this.loading = false;
                        this.$toast.error("Login expired. Kindly refresh the page and try again.", "", {
                            timeout: 5000,
                        });
                    }
                });
            }
           
            
        }
    }
})


