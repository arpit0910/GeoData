<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApiLogController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\SubRegionController;
use App\Http\Controllers\Admin\TimezoneController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\PincodeController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\WebsiteQueryController;
use App\Http\Controllers\Admin\SubscriptionAdminController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\TicketCategoryController;
use App\Http\Controllers\Admin\TicketSubCategoryController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\CurrencyConversionController as AdminCurrencyConversionController;
use App\Http\Controllers\Admin\BankController;
use App\Http\Controllers\Admin\BankBranchController;
use App\Http\Controllers\CurrencyConversionController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/landing-v1', [HomeController::class, 'landingV1'])->name('landing.v1');
Route::get('/landing-v2', [HomeController::class, 'landingV2'])->name('landing.v2');
Route::get('/landing-v3', [HomeController::class, 'landingV3'])->name('landing.v3');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'sendContact'])->name('contact.post');
Route::get('/pricing', [HomeController::class, 'pricing'])->name('pricing');
Route::get('/subscribe', [SubscriptionController::class, 'pricing'])->name('subscription.pricing')->middleware('auth');

Route::get('/docs', [HomeController::class, 'docs'])->name('docs');
Route::get('/status', [HomeController::class, 'status'])->name('status');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');

Route::middleware('guest')->group(function() {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'profile.complete.check', 'subscribed'])->name('dashboard');

Route::middleware(['auth', 'admin'])->group(function () {

    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/list', [UserController::class, 'index'])->name('list');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/store', [UserController::class, 'store'])->name('store');
        Route::get('/show/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/edit/{user}', [UserController::class, 'edit'])->name('edit');
        Route::put('/update/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/delete/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/toggle-status/{user}', [UserController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::prefix('countries')->name('countries.')->group(function () {
        Route::get('/', [CountryController::class, 'index'])->name('index');
        Route::post('/import', [CountryController::class, 'import'])->name('import');
        Route::get('/create', [CountryController::class, 'create'])->name('create');
        Route::post('/', [CountryController::class, 'store'])->name('store');
        Route::get('/{country}/edit', [CountryController::class, 'edit'])->name('edit');
        Route::put('/{country}', [CountryController::class, 'update'])->name('update');
        Route::delete('/{country}', [CountryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('regions')->name('regions.')->group(function () {
        Route::get('/', [RegionController::class, 'index'])->name('index');
        Route::post('/import', [RegionController::class, 'import'])->name('import');
        Route::get('/create', [RegionController::class, 'create'])->name('create');
        Route::post('/', [RegionController::class, 'store'])->name('store');
        Route::get('/{region}/edit', [RegionController::class, 'edit'])->name('edit');
        Route::put('/{region}', [RegionController::class, 'update'])->name('update');
        Route::delete('/{region}', [RegionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('subregions')->name('subregions.')->group(function () {
        Route::get('/', [SubRegionController::class, 'index'])->name('index');
        Route::post('/import', [SubRegionController::class, 'import'])->name('import');
        Route::get('/create', [SubRegionController::class, 'create'])->name('create');
        Route::post('/', [SubRegionController::class, 'store'])->name('store');
        Route::get('/{subregion}/edit', [SubRegionController::class, 'edit'])->name('edit');
        Route::put('/{subregion}', [SubRegionController::class, 'update'])->name('update');
        Route::delete('/{subregion}', [SubRegionController::class, 'destroy'])->name('destroy');
    });

    Route::get('countries/{country}/timezones', [TimezoneController::class, 'getByCountry'])->name('countries.timezones');
    Route::post('timezones/import', [TimezoneController::class, 'import'])->name('timezones.import');
    Route::resource('timezones', TimezoneController::class);

    Route::post('states/import', [StateController::class, 'import'])->name('states.import');
    Route::resource('states', StateController::class);

    Route::post('cities/import', [CityController::class, 'import'])->name('cities.import');
    Route::resource('cities', CityController::class);

    Route::resource('banks', BankController::class);
    Route::resource('bank-branches', BankBranchController::class);

    Route::post('pincodes/upload-chunk', [PincodeController::class, 'uploadChunk'])->name('pincodes.uploadChunk');
    Route::get('pincodes/states/{country}', [PincodeController::class, 'statesByCountry'])->name('pincodes.states-by-country');
    Route::get('pincodes/cities/{state}', [PincodeController::class, 'citiesByState'])->name('pincodes.cities-by-state');
    Route::resource('pincodes', PincodeController::class);

    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::post('/', [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{plan}/sync', [PlanController::class, 'syncToGateway'])->name('sync');
    });

    Route::prefix('admin/subscriptions')->name('admin.subscriptions.')->group(function () {
        Route::get('/', [SubscriptionAdminController::class, 'index'])->name('index');
        Route::get('/{subscription}', [SubscriptionAdminController::class, 'show'])->name('show');
        Route::post('/{subscription}/assign-credits', [SubscriptionAdminController::class, 'assignCredits'])->name('assign-credits');
    });

    Route::resource('coupons', CouponController::class, ['as' => 'admin']);
    Route::post('coupons/{coupon}/toggle-status', [CouponController::class, 'toggleStatus'])->name('admin.coupons.toggle-status');

    Route::prefix('admin-transactions')->name('admin.transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
    });

    Route::prefix('website-queries')->name('admin.website-queries.')->group(function () {
        Route::get('/', [WebsiteQueryController::class, 'index'])->name('index');
        Route::get('/{websiteQuery}', [WebsiteQueryController::class, 'show'])->name('show');
        Route::delete('/{websiteQuery}', [WebsiteQueryController::class, 'destroy'])->name('destroy');
        Route::post('/{websiteQuery}/mark-viewed', [WebsiteQueryController::class, 'markAsViewed'])->name('mark-viewed');
    });

    // Ticketing System
    Route::resource('ticket-categories', TicketCategoryController::class, ['as' => 'admin']);
    Route::post('ticket-categories/{ticketCategory}/toggle-status', [TicketCategoryController::class, 'toggleStatus'])->name('admin.ticket-categories.toggle-status');
    
    Route::resource('ticket-sub-categories', TicketSubCategoryController::class, ['as' => 'admin']);
    Route::post('ticket-sub-categories/{ticketSubCategory}/toggle-status', [TicketSubCategoryController::class, 'toggleStatus'])->name('admin.ticket-sub-categories.toggle-status');

    Route::prefix('tickets')->name('admin.tickets.')->group(function () {
        Route::get('/', [AdminTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [AdminTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/resolve', [AdminTicketController::class, 'resolve'])->name('resolve');
    });

    // FAQ Management
    Route::resource('faqs', AdminFaqController::class);
    Route::post('faqs/{faq}/toggle-status', [AdminFaqController::class, 'toggleStatus'])->name('faqs.toggle-status');

    // Currency Conversions
    Route::prefix('currency-conversions')->name('admin.currency-conversions.')->group(function () {
        Route::get('/', [AdminCurrencyConversionController::class, 'index'])->name('index');
        Route::post('/sync', [AdminCurrencyConversionController::class, 'sync'])->name('sync');
    });

    // Server Logs
    Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->name('admin.logs');

});

Route::middleware(['auth'])->group(function () {
    Route::get('/complete-profile', [AuthController::class, 'completeProfile'])->name('profile.complete');
    Route::post('/complete-profile', [AuthController::class, 'saveProfile'])->name('profile.complete.post');
    Route::get('/api/pincode/{pincode}', [PincodeController::class, 'lookup'])->name('api.pincode.lookup');
    Route::get('/api/currency/{currency}', [CurrencyConversionController::class, 'lookup'])->name('api.currency.lookup');

    Route::middleware(['profile.complete.check'])->group(function () {
        Route::post('/pricing/{plan}/order', [SubscriptionController::class, 'createOrder'])->name('pricing.order');
        Route::post('/pricing/verify', [SubscriptionController::class, 'verifyPayment'])->name('pricing.verify');
        Route::post('/pricing/validate-coupon', [SubscriptionController::class, 'validateCoupon'])->name('pricing.validate-coupon');

        Route::middleware(['subscribed'])->group(function () {
            Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
            Route::get('/api-keys', [ProfileController::class, 'apiKeys'])->name('api-keys.index');
            Route::post('/api-keys/regenerate', [ProfileController::class, 'regenerateApiKeys'])->name('api-keys.regenerate');
            Route::get('/api-logs', [ApiLogController::class, 'index'])->name('api-logs.index');
            Route::get('/api-logs/latest-id', [ApiLogController::class, 'latestId'])->name('api-logs.latest-id');
            Route::get('/transactions', [SubscriptionController::class, 'transactions'])->name('transactions.index');
            Route::get('/transactions/{transaction}/receipt', [SubscriptionController::class, 'downloadReceipt'])->name('pricing.receipt');
        });

        // Help & Support (Accessible without subscription)
        Route::get('/help-support', [TicketController::class, 'index'])->name('support.index');
        Route::post('/help-support', [TicketController::class, 'store'])->name('support.store');
        Route::get('/help-support/sub-categories/{category}', [TicketController::class, 'getSubCategories'])->name('support.sub-categories');
    });
});
