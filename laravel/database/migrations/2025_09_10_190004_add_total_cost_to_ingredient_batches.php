use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ingredient_batches', function (Blueprint $table) {
            $table->decimal('total_cost', 12, 2)->default(0)->after('notes');
        });
    }
    public function down(): void {
        Schema::table('ingredient_batches', function (Blueprint $table) {
            $table->dropColumn('total_cost');
        });
    }
};
