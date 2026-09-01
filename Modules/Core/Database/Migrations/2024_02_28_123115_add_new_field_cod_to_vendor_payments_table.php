<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Core\Entities\Vendor\Vendor;
use Modules\Core\Entities\VendorPayment;
use Modules\Payment\Entities\Payment;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $vendors = Vendor::where(Vendor::status, 2)->get();

        $payment = Payment::where(Payment::id, 'payment00009')->first();

        if ($payment && ! $vendors->isEmpty()) {
            foreach ($vendors as $vendor) {
                $vendorPayment = new VendorPayment;
                $vendorPayment->payment_id = $payment->id;
                $vendorPayment->vendor_id = $vendor->id;
                $vendorPayment->status = $payment->id == 'payment00009' ? 1 : 0;
                $vendorPayment->added_user_id = 1;
                $vendorPayment->save();
            }
        } else {
            // Log or output a message indicating that the payment doesn't exist
            // echo "Payment with ID 'payment00009' does not exist or no vendors with status 2 found.";
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
