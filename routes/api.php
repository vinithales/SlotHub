<?php



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Business;

//Apgar
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Credenciais inválidas'], 401);
    }

    return ['token' => $user->createToken('api')->plainTextToken];
});

Route::post('/register', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    return response()->json(['message' => 'User created successfully'], 201);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1/schedules')->middleware('api')->group(function () {
    // Rotas públicas (sem autenticação)
    Route::get('/availability', [ScheduleController::class, 'listAvailability']);

    // Rotas autenticadas
    Route::middleware('auth:sanctum')->group(function () {
        // Gerenciamento de agendas
        Route::post('/', [ScheduleController::class, 'generateSchedule']);
        Route::put('/config', [ScheduleController::class, 'updateConfig']);

        // Rotas administrativas
        Route::prefix('admin')->middleware('can:manage-schedules')->group(function () {
            Route::post('/recalculate', [ScheduleController::class, 'recalculateAll']);
            Route::post('/block-slots', [ScheduleController::class, 'blockSlots']);
        });
    });
});


//apagar depois
Route::post('/business', function (Request $request) {
    $slug = Str::slug($request->input('name'));

    // Você pode adicionar um sufixo caso queira garantir unicidade:
    $slugBase = $slug;
    $count = 1;
    while (Business::where('slug', $slug)->exists()) {
        $slug = $slugBase . '-' . $count++;
    }

    $business = Business::create([
        'name' => $request->input('name'),
        'timezone' => $request->input('timezone', 'America/Sao_Paulo'),
        'config' => json_encode([
            'days' => ['monday', 'wednesday', 'friday'],
            'valid_from' => '09:00',
            'valid_to' => '17:00',
            'interval' => 30
        ]),
        'slug' => $slug,
    ]);

    return response()->json($business);
});
