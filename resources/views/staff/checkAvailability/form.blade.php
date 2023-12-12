<form wire:submit="search" class="">
	<div class="row">
		<div class="form-group col-md-4">
			<label for="exampleInputEmail1">StartDate</label>
			<input wire:model.live="startDate" type="date" class="form-control">
		</div>
		<div class="form-group col-md-4">
			<label for="exampleInputEmail1">Nights</label>
			<input wire:model.live="nights" type="number" class="form-control">
		</div>
		<div class="form-group col-md-4">
			<label for="exampleInputEmail1">People</label>
			<input wire:model.live="people" type="number" class="form-control">
		</div>
	</div>
	<div class="row">
		<div class="form-group col-md-4">
			<label for="exampleInputEmail1">roomType</label>
			<select wire:model.live="roomType" class="form-control">
				<option value="dorm">dorm</option>
				<option value="private">private</option>
			</select>
		</div>
		<div class="form-group col-md-4">
			<label for="exampleInputEmail1">currency</label>
			<select wire:model.live="currency" class="form-control">
				<option value="USD">USD</option>
				<option value="EUR">EUR</option>
			</select>
		</div>
		<div class="form-group col-md-4">
			<label for="exampleInputEmail1">guestCountryCode</label>
			<select wire:model.live="guestCountryCode" class="form-control">
				<option value="{{ $this->guestCountryCode }}">{{ $this->guestCountryCode }}</option>
				<option value="US">US</option>
				<option value="ES">ES</option>
				<option value="GB">GB</option>
				<option value="TR">TR</option>
			</select>
		</div>
	</div>

	<div class="row">
		<div class="form-group col-md-12">
			<button type="submit" class="pull-right btn btn-default">Submit</button>
		</div>
	</div>
</form>