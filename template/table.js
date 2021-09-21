Vue.component('cms-statistic-table', {
	props: ['id'],
	template: `<cms-card>
	<div class="container-fluid">
		<div v-if="fields === null" class="text-center my-5">
			<b-spinner></b-spinner>
		</div>
		<template v-else>
			<b-button variant="secondary" v-b-modal.modal-sql>Edit SQL</b-button>
			<b-button variant="secondary" v-b-modal.modal-add-field>Add field</b-button>
			<b-card class="mt-3">
				<div class="row">
					<div v-for="field in fields" class="col-sm-4">
						<label :for="field.name">{{ field.name }}:</label>
						<template v-if="field.type === 'enum'">
							<b-form-select :id="field.name" v-model="field.haystack" :options="field.values"></b-form-select>
						</template>
						<template v-else-if="field.type === 'datetime'">
							<b-form-datepicker :id="field.name" v-model="field.haystack"></b-form-datepicker>
						</template>
						<template v-else>
							<b-form-input :id="field.name" v-model="field.haystack"></b-form-input>
						</template>
					</div>
				</div>
				<b-button variant="primary" class="mt-3" :disabled="usedAll === false" @click="run">Export statistic</b-button>
			</b-card>
			<b-alert variant="danger" :show="usedAll === false" class="mt-3">
				Please define all mandatory fields:
				<ul class="mb-0">
					<li v-for="mandatoryVariable in variables">{{ mandatoryVariable }}</li>
				</ul>
			</b-alert>
			<div class="mt-3">
				<div v-if="loadingTable" class="text-center my-5">
					<b-spinner></b-spinner><br>
					<span class="text-secondary">Computing...</span>
				</div>
				<div v-if="tableHeader !== null">
					<table class="table table-sm table-bordered">
						<tr>
							<th v-for="tableHeaderItem in tableHeader">{{ tableHeaderItem }}</th>
						</tr>
						<tr v-for="tableLine in tableBody">
							<td v-for="tableHeaderItem in tableHeader">
								{{ tableLine[tableHeaderItem] }}
							</td>
						</tr>
					</table>
				</div>
			</div>
		</template>
	</div>
	<b-modal id="modal-add-field" title="Add new field" hide-footer>
		<b-form @submit="addField">
			<div class="mb-3">
				Name:
				<b-form-input v-model="newFieldForm.name"></b-form-input>
			</div>
			<div class="mb-3">
				Type:
				<b-form-select v-model="newFieldForm.type" :options="fieldTypes"></b-form-select>
			</div>
			<div v-if="newFieldForm.type === 'enum'" class="mb-3">
				SQL for load values (<code>id</code> and <code>value</code> column):
				<b-form-textarea v-model="newFieldForm.valuesSql" rows="10"></b-form-textarea>
			</div>
			<b-button type="submit" variant="primary" class="mt-3">Add field</b-button>
		</b-form>
	</b-modal>
	<b-modal id="modal-sql" title="SQL inspector" size="lg" hide-footer>
		<b-form-textarea v-model="sql" rows="15"></b-form-textarea>
		<b-button @click="saveSql" variant="primary" class="mt-3">Save</b-button>
	</b-modal>
</cms-card>`,
	data() {
		return {
			fields: null,
			fieldTypes: [],
			sql: '',
			variables: [],
			usedAll: false,
			newFieldForm: {
				name: '',
				type: '',
				valuesSql: ''
			},
			tableHeader: null,
			tableBody: null,
			loadingTable: false
		}
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get('cms-statistic/detail?id=' + this.id)
				.then(req => {
					this.fields = req.data.fields;
					this.fieldTypes = req.data.fieldTypes;
					this.sql = req.data.sql;
					this.variables = req.data.variables;
					this.usedAll = req.data.usedAll;
				});
		},
		addField(evt) {
			evt.preventDefault();
			axiosApi.post('cms-statistic/add-field', {
				id: this.id,
				name: this.newFieldForm.name,
				type: this.newFieldForm.type,
				valuesSql: this.newFieldForm.valuesSql
			}).then(req => {
				this.sync();
			});
		},
		saveSql() {
			axiosApi.post('cms-statistic/save-sql', {
				id: this.id,
				sql: this.sql
			}).then(req => {
				this.sync();
			});
		},
		run() {
			this.loadingTable = true;
			axiosApi.post('cms-statistic/load-table', {
				id: this.id,
				fields: this.fields
			}).then(req => {
				this.loadingTable = false;
				this.tableHeader = req.data.header;
				this.tableBody = req.data.body;
			});
		}
	}
});
