<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Conversations</h5>
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
                                        <table ref="conversations" id="conversation-records" class="table table-striped table-bordered zero-configuration">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Customer</th>
                                                    <th>Vendor</th>
                                                    <th style="min-width: 350px">Last Message</th>
                                                    <th>L.U. Timestamp</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody> 
                                                    <tr v-for="conversation in conversations" :key="conversation.id"> 
                                                        <td>{{ conversation.id }}</td>
                                                        <td>{{ conversation.customer.first_name+" "+conversation.customer.last_name }}</td>
                                                        <td>{{ conversation.vendor.name }}</td>
                                                        <td><div v-if="conversation.last_message">{{ conversation.last_message.message_content }}</div></td>
                                                        <td><div v-if="conversation.last_message">{{ conversation.last_message.message_timestamp }}</div></td>
                                                        <td>
                                                            <a :href="'https://www.solushop.com.gh/portal/manager/conversation/'+conversation.id">
                                                                <button  data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="View Conversation" style="margin-top: 3px;" class="btn btn-info btn-sm round">
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
            conversations: null,
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
        this.dt = $(this.$refs.conversations).DataTable();
        await this.updateRecords(this.filter);
        this.loading = false;
        setInterval(this.updateCounts, 10000);
    },


    methods: {
        setCount(count){
            if (this.count.old == 0) {
                this.count.old = count;
            }
            this.count.new = count;
        },

        async updateCounts(){
            var countResponse = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/conversations/count',
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
                            

            this.setCount(countResponse.data.count);
            if (this.count.old != this.count.new) {
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
                                url: 'https://www.solushop.com.gh/portal/manager/conversations/records',
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
            this.conversations = recordsResponse.data.records;
            this.setCount(recordsResponse.data.count);
        },
    },
    
    watch: {
        conversations(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.conversations).DataTable({
                    "order": [
                        [4, 'desc']
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