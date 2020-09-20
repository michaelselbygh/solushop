<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title" style="margin-top: 10px;">{{ type }} Flags</h5>
                                </div>
                                <div class="col-md-6" style="text-align: right">
                                    <button v-if="type=='Active'" @click="toggleType('Deleted')" style="margin-bottom: 10px;" class="btn btn-danger btn-sm round">
                                        Show Deleted Messages
                                    </button>
                                    <button v-if="type=='Deleted'" @click="toggleType('Active')" style="margin-bottom: 10px;" class="btn btn-success btn-sm round">
                                        Show Active Flags
                                    </button>
                                </div>
                            </div>
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
                                        <table ref="flags" id="flag-records" class="table table-striped table-bordered zero-configuration">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Sender</th>
                                                    <th style="min-width: 350px">Message</th>
                                                    <th>Timestamp</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody v-if="flags"> 
                                                <tr v-for="flag in flags" :key="flag.id"> 
                                                    <td>{{ flag.id }}</td>
                                                    <td>{{ sender(flag) }}</td>
                                                    <td>{{ message(flag) }}</td>
                                                    <td>{{ flag.created_at }}</td>
                                                    <td v-if="type == 'Active'">
                                                        <a :href="'https://www.solushop.com.gh/portal/manager/conversation/'+flag.message.message_conversation_id">
                                                            <button  data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="View Conversation" style="margin-top: 3px;" class="btn btn-info btn-sm round">
                                                                <i class="ft-eye"></i>
                                                            </button>
                                                        </a>
                                                        <button  data-toggle="tooltip" @click="action(flag.mf_mid, 'approve')" data-popup="tooltip-custom" data-original-title="Mark as safe" style="margin-top: 3px;" class="btn btn-success btn-sm round">
                                                            <i class="ft-check"></i>
                                                        </button>
                                                        <button  data-toggle="tooltip" @click="action(flag.mf_mid, 'reject')" data-popup="tooltip-custom" data-original-title="Unsafe, delete message" style="margin-top: 3px;" class="btn btn-danger btn-sm round">
                                                            <i class="ft-x"></i>
                                                        </button>
                                                    </td>
                                                    <td v-if="type == 'Deleted'">
                                                        <a :href="'https://www.solushop.com.gh/portal/manager/conversation/'+flag.message.message_conversation_id">
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
            flags: null,
            type: "Active",
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
        this.dt = $(this.$refs.flags).DataTable();
        await this.updateRecords();
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
            var response = await axios({
                                method: 'post',
                                data: {
                                    type: this.type
                                },
                                url: 'https://www.solushop.com.gh/portal/manager/messages/flags/count',
                            });

            this.setCount(response.data.count);
            if (this.count.old != this.count.new) {
                this.loading = true;

                //update old counts
                this.count.old = this.count.new;

                await this.updateRecords();
                this.loading = false;
            }
        },

        async updateRecords(){
            var response = await axios({
                                method: 'post',
                                data: {
                                    type: this.type
                                },
                                url: 'https://www.solushop.com.gh/portal/manager/messages/flags/records',
                            });
            this.flags = response.data.records;
            this.setCount(response.data.count);
        },

        async toggleType(type){
            this.loading = true;
            this.type = type;
            await this.updateRecords();
            this.count.old = this.count.new;
            this.loading = false;
        },

        async action(id, action){
            this.loading = true;
            var response = await axios({
                method: 'post',
                data: {
                    action: action,
                    id: id
                },
                url: 'https://www.solushop.com.gh/portal/manager/messages/flags/action',
            });
            await this.updateRecords();
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

        },

        message(flag){
            if (this.type == "Active") {
                return flag.message.message_content
            }else{
                return flag.message_content
            }
        },

        sender(flag){
            if (this.type == "Active") {
                return flag.message.sender
            }else{
                return flag.sender
            }
        },
    },
    
    watch: {
        flags(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.flags).DataTable({
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