Vue.component('cms-statistic-default', {
	template: `<div class="container-fluid">
	<div class="row mt-2">
		<div class="col">
			<h1>Statistic</h1>
		</div>
		<div class="col-3 text-right">
			<b-button variant="primary" v-b-modal.modal-new-statistic>Create statistic</b-button>
		</div>
	</div>
	<div v-if="statistics === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<b-card v-else>
		<table class="table table-sm cms-table-no-border-top">
			<tr>
				<th>Name</th>
				<th>Last indexed</th>
			</tr>
			<tr v-for="statistic in statistics">
				<td>
					<a :href="link('Statistic:detail', {id: statistic.id})">
						{{ statistic.name }}
					</a>
				</td>
				<td>{{ statistic.lastIndex }}</td>
			</tr>
		</table>
	</b-card>
	<b-modal id="modal-new-statistic" title="Create statistic" hide-footer>
		<div v-if="newForm.loading" class="text-center my-5">
			<b-spinner></b-spinner>
		</div>
		<template v-else>
			<b-form @submit="createStatistic">
				<div class="mb-3">
					Name:
					<b-form-input v-model="newForm.name"></b-form-input>
				</div>
				<div class="mb-3">
					SQL:
					<b-form-textarea v-model="newForm.sql" rows="10"></b-form-textarea>
				</div>
				<b-button type="submit" variant="primary" class="mt-3">Create new statistic</b-button>
			</b-form>
		</template>
	</b-modal>
</div>`,
	data() {
		return {
			statistics: null,
			newForm: {
				loading: false,
				name: '',
				sql: ''
			}
		};
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get('cms-statistic')
				.then(req => {
					this.statistics = req.data.statistics;
				});
		},
		createStatistic(evt) {
			evt.preventDefault();
			this.newForm.loading = true;
			axiosApi.post('cms-statistic/create-statistic', {
				name: this.newForm.name,
				sql: this.newForm.sql
			}).then(req => {
				this.newForm.loading = false;
				this.sync();
			});
		}
	}
});
