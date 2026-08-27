<?php

use App\Http\Controllers\Api\V1\IndustryPackageController;
use App\Http\Controllers\Api\V1\PlatformController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:public-api')->group(function () {
    Route::get('/industry-packages', [IndustryPackageController::class, 'index'])->name('api.v1.industry-packages.index');
    Route::get('/industry-packages/{industry}', [IndustryPackageController::class, 'show'])->name('api.v1.industry-packages.show');
    Route::get('/industry-packages/{industry}/dashboard', [IndustryPackageController::class, 'dashboard'])->name('api.v1.industry-packages.dashboard');
    Route::prefix('retail/ecommerce/{integrationId}')->name('api.v1.public.retail.ecommerce.')->group(function () {
        Route::get('/products', [\Modules\Retail\Controllers\Api\RetailEcommerceCatalogController::class, 'products'])->name('products');
        Route::get('/categories', [\Modules\Retail\Controllers\Api\RetailEcommerceCatalogController::class, 'categories'])->name('categories');
        Route::get('/pricing', [\Modules\Retail\Controllers\Api\RetailEcommerceCatalogController::class, 'pricing'])->name('pricing');
    });
});

Route::prefix('v1')->middleware(['auth', 'tenant.context', 'throttle:api'])->group(function () {
    Route::get('/context', [PlatformController::class, 'context'])->name('api.v1.context');
    Route::get('/tenant/industry-package', [IndustryPackageController::class, 'tenant'])->name('api.v1.tenant.industry-package');

    Route::prefix('industries/retail')->name('api.v1.retail.')->middleware('module.enabled:retail')->group(function () {
        Route::get('/dashboard', [\Modules\Retail\Controllers\Api\RetailApiController::class, 'dashboard'])->middleware('permission:retail.view')->name('dashboard');
        Route::get('/products', [\Modules\Retail\Controllers\Api\RetailApiController::class, 'products'])->middleware('permission:retail.products.view')->name('products.index');
        Route::get('/promotions', [\Modules\Retail\Controllers\Api\RetailApiController::class, 'promotions'])->middleware('permission:retail.promotions.view')->name('promotions.index');
        Route::post('/promotions', [\Modules\Retail\Controllers\Api\RetailApiController::class, 'createPromotion'])->middleware('permission:retail.promotions.manage')->name('promotions.store');
        Route::post('/orders', [\Modules\Retail\Controllers\Api\RetailApiController::class, 'createOrder'])->middleware('permission:retail.orders.manage')->name('orders.store');
        Route::get('/customers/{client}/loyalty', [\Modules\Retail\Controllers\Api\RetailApiController::class, 'loyalty'])->middleware('permission:retail.loyalty.view')->name('customers.loyalty');
        Route::post('/gift-cards', [\Modules\Retail\Controllers\Api\RetailApiController::class, 'issueGiftCard'])->middleware('permission:retail.gift-cards.manage')->name('gift-cards.store');
        Route::get('/gift-cards/{giftCard}/balance', [\Modules\Retail\Controllers\Api\RetailApiController::class, 'giftCardBalance'])->middleware('permission:retail.gift-cards.view')->name('gift-cards.balance');
        Route::post('/scan/product', [\Modules\Retail\Controllers\Api\SmartScanningApiController::class, 'product'])->middleware('permission:retail.scanning.manage')->name('scan.product');
        Route::post('/scan/verify', [\Modules\Retail\Controllers\Api\SmartScanningApiController::class, 'verify'])->middleware('permission:retail.scanning.view')->name('scan.verify');
        Route::post('/scan/camera', [\Modules\Retail\Controllers\Api\SmartScanningApiController::class, 'camera'])->middleware('permission:retail.scanning.manage')->name('scan.camera');
        Route::post('/scan/self-checkout', [\Modules\Retail\Controllers\Api\SmartScanningApiController::class, 'selfCheckout'])->middleware('permission:retail.scanning.self-checkout')->name('scan.self-checkout');
        Route::get('/scan/history', [\Modules\Retail\Controllers\Api\SmartScanningApiController::class, 'history'])->middleware('permission:retail.scanning.view')->name('scan.history');
        Route::get('/scan/analytics', [\Modules\Retail\Controllers\Api\SmartScanningApiController::class, 'analytics'])->middleware('permission:retail.scanning.reports')->name('scan.analytics');
    });

    Route::prefix('industries/salon')->name('api.v1.salon.')->middleware('module.enabled:salon')->group(function () {
        Route::get('/dashboard', [\Modules\Salon\Controllers\Api\SalonApiController::class, 'dashboard'])->middleware('permission:salon.view')->name('dashboard');
        Route::get('/appointments', [\Modules\Salon\Controllers\Api\SalonApiController::class, 'appointments'])->middleware('permission:salon.appointments.view')->name('appointments.index');
        Route::post('/appointments', [\Modules\Salon\Controllers\Api\SalonApiController::class, 'bookAppointment'])->middleware('permission:salon.appointments.manage')->name('appointments.store');
        Route::post('/appointments/{appointment}/complete', [\Modules\Salon\Controllers\Api\SalonApiController::class, 'completeAppointment'])->middleware('permission:salon.appointments.manage')->name('appointments.complete');
        Route::get('/services', [\Modules\Salon\Controllers\Api\SalonApiController::class, 'services'])->middleware('permission:salon.services.view')->name('services.index');
        Route::get('/clients', [\Modules\Salon\Controllers\Api\SalonApiController::class, 'clients'])->middleware('permission:salon.loyalty.view')->name('clients.index');
    });

    Route::prefix('industries/real-estate')->name('api.v1.real-estate.')->middleware('module.enabled:real-estate')->group(function () {
        Route::get('/dashboard', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'dashboard'])->middleware('permission:real-estate.view')->name('dashboard');
        Route::get('/listings', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'listings'])->middleware('permission:real-estate.listings.view')->name('listings.index');
        Route::get('/leases/{lease}', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'lease'])->middleware('permission:real-estate.leases.view')->name('leases.show');
        Route::get('/tenant-portal/{tenant}', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'tenantPortal'])->middleware('permission:real-estate.tenants.view')->name('tenant-portal.show');
        Route::get('/tenants/{tenant}/ledger', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'tenantLedger'])->middleware('permission:real-estate.tenant-ledger.view')->name('tenants.ledger');
        Route::get('/tenants/{tenant}/payments', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'tenantPayments'])->middleware('permission:real-estate.tenant-ledger.view')->name('tenants.payments');
        Route::get('/tenants/{tenant}/statements', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'tenantStatements'])->middleware('permission:real-estate.tenant-ledger.view')->name('tenants.statements');
        Route::get('/tenants/{tenant}/utilities', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'tenantUtilities'])->middleware('permission:real-estate.utilities.view')->name('tenants.utilities');
        Route::get('/tenants/archive', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'tenantArchive'])->middleware('permission:real-estate.tenants.view')->name('tenants.archive.index');
        Route::get('/tenants/{tenant}/offboarding', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'tenantOffboarding'])->middleware('permission:real-estate.tenants.offboard')->name('tenants.offboarding.show');
        Route::post('/tenants/{tenant}/notice', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'startTenantNotice'])->middleware('permission:real-estate.tenants.offboard')->name('tenants.notice');
        Route::post('/tenants/{tenant}/offboarding', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'progressTenantExit'])->middleware('permission:real-estate.tenants.offboard')->name('tenants.offboarding');
        Route::post('/tenants/{tenant}/archive', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'archiveTenant'])->middleware('permission:real-estate.tenants.offboard')->name('tenants.archive');
        Route::post('/tenants/{tenant}/restore', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'restoreTenant'])->middleware('permission:real-estate.tenants.offboard')->name('tenants.restore');
        Route::get('/buyer-portal/{buyer}', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'buyerPortal'])->middleware('permission:real-estate.buyers.view')->name('buyer-portal.show');
        Route::get('/agent-portal/{agent}', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'agentPortal'])->middleware('permission:real-estate.agents.view')->name('agent-portal.show');
        Route::post('/service-requests', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'serviceRequest'])->middleware('permission:real-estate.service-requests.manage')->name('service-requests.store');
        Route::post('/utility/readings', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'utilityReading'])->middleware('permission:real-estate.utilities.manage')->name('utility.readings.store');
        Route::post('/utility/bills', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'utilityBill'])->middleware('permission:real-estate.utilities.manage')->name('utility.bills.store');
        Route::get('/reports/{type}/export', [\Modules\RealEstate\Controllers\Api\RealEstateApiController::class, 'reportExport'])->whereIn('type', ['tenant-payments', 'tenant-ledger', 'utility-billing', 'move-outs', 'archived-tenants', 'tenant-history', 'vacancy', 'lease-billing', 'outstanding-balances', 'commissions', 'maintenance', 'service-requests', 'inspections', 'valuations', 'land', 'development'])->middleware('permission:real-estate.reports')->name('reports.export');
    });

    Route::prefix('industries/agriculture')->name('api.v1.agriculture.')->middleware('module.enabled:agriculture')->group(function () {
        Route::get('/dashboard', [\Modules\Agriculture\Controllers\Api\AgricultureApiController::class, 'dashboard'])->middleware('permission:agriculture.view')->name('dashboard');
        Route::post('/records/{type}', [\Modules\Agriculture\Controllers\Api\AgricultureApiController::class, 'store'])->middleware('permission:agriculture.view')->name('records.store');
        Route::get('/reports/{type}/export', [\Modules\Agriculture\Controllers\Api\AgricultureApiController::class, 'reportExport'])->whereIn('type', ['farms', 'fields', 'crop-plans', 'activities', 'harvests', 'livestock', 'veterinary', 'inputs', 'equipment', 'equipment-maintenance', 'sales', 'finance', 'compliance'])->middleware('permission:agriculture.reports')->name('reports.export');
        Route::get('/traceability/{batch}', [\Modules\Agriculture\Controllers\Api\AgricultureApiController::class, 'traceability'])->middleware('permission:agriculture.view')->name('traceability.show');
    });

    Route::prefix('industries/construction')->name('api.v1.construction.')->middleware('module.enabled:construction')->group(function () {
        Route::get('/dashboard', [\Modules\Construction\Controllers\Api\ConstructionApiController::class, 'dashboard'])->middleware('permission:construction.dashboard')->name('dashboard');
        Route::get('/projects', [\Modules\Construction\Controllers\Api\ConstructionApiController::class, 'projects'])->middleware('permission:construction.view')->name('projects.index');
        Route::get('/reports/{type}/export', [\Modules\Construction\Controllers\Api\ConstructionApiController::class, 'reportExport'])->whereIn('type', ['projects', 'boqs', 'materials', 'certificates', 'site-reports'])->middleware('permission:construction.reports')->name('reports.export');
    });

    Route::prefix('industries/printing-branding')->name('api.v1.printing-branding.')->middleware('module.enabled:printing-branding')->group(function () {
        Route::get('/dashboard', [\Modules\PrintingBranding\Controllers\Api\PrintingApiController::class, 'dashboard'])->middleware('permission:printing.dashboard')->name('dashboard');
        Route::get('/mobile/jobs/{job}', [\Modules\PrintingBranding\Controllers\Api\PrintingApiController::class, 'mobileJob'])->middleware('permission:production.execute')->name('mobile.jobs.show');
        Route::post('/mobile/jobs/{job}/status', [\Modules\PrintingBranding\Controllers\Api\PrintingApiController::class, 'updateStatus'])->middleware('permission:production.execute')->name('mobile.jobs.status');
        Route::post('/mobile/waste', [\Modules\PrintingBranding\Controllers\Api\PrintingApiController::class, 'recordWaste'])->middleware('permission:production.execute')->name('mobile.waste.store');
        Route::get('/reports/{type}/export', [\Modules\PrintingBranding\Controllers\Api\PrintingApiController::class, 'reportExport'])->whereIn('type', ['jobs', 'estimates', 'waste', 'daily-production'])->middleware('permission:printing_reports.view')->name('reports.export');
    });

    Route::prefix('industries/automotive')->name('api.v1.automotive.')->middleware('module.enabled:automotive')->group(function () {
        Route::get('/dashboard', [\Modules\Automotive\Controllers\Api\AutomotiveApiController::class, 'dashboard'])->middleware('permission:automotive.dashboard')->name('dashboard');
        Route::get('/vehicles', [\Modules\Automotive\Controllers\Api\AutomotiveApiController::class, 'vehicles'])->middleware('permission:vehicles.view')->name('vehicles.index');
        Route::get('/job-cards', [\Modules\Automotive\Controllers\Api\AutomotiveApiController::class, 'jobCards'])->middleware('permission:job_cards.view')->name('job-cards.index');
        Route::get('/reports/{type}/export', [\Modules\Automotive\Controllers\Api\AutomotiveApiController::class, 'reportExport'])->whereIn('type', ['vehicles', 'job-cards', 'parts', 'estimates', 'job-costing', 'service-reminders', 'specialty'])->middleware('permission:automotive.reports')->name('reports.export');
    });

    Route::prefix('shared/compliance/etims')->name('api.v1.etims.')->group(function () {
        Route::get('/dashboard', [\Shared\Compliance\Etims\Controllers\EtimsComplianceController::class, 'dashboard'])->middleware('permission:etims.view')->name('dashboard');
        Route::get('/submissions', [\Shared\Compliance\Etims\Controllers\EtimsComplianceController::class, 'submissions'])->middleware('permission:etims.view')->name('submissions.index');
        Route::post('/submissions/retry', [\Shared\Compliance\Etims\Controllers\EtimsComplianceController::class, 'retry'])->middleware('permission:etims.retry')->name('submissions.retry');
    });

    Route::prefix('shared/communication')->name('api.v1.communication.')->group(function () {
        Route::get('/channels', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'channels'])->middleware('permission:communication.view')->name('channels.index');
        Route::post('/channels', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'storeChannel'])->middleware('permission:communication.create_channel')->name('channels.store');
        Route::post('/channels/{channel}/read', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'markRead'])->middleware('permission:communication.view')->name('channels.read');
        Route::get('/messages', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'messages'])->middleware('permission:communication.view')->name('messages.index');
        Route::post('/messages', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'storeMessage'])->middleware('permission:communication.send')->name('messages.store');
        Route::post('/messages/{message}/reactions', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'react'])->middleware('permission:communication.send')->name('messages.reactions.store');
        Route::post('/messages/{message}/save', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'save'])->middleware('permission:communication.view')->name('messages.save');
        Route::delete('/messages/{message}/save', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'unsave'])->middleware('permission:communication.view')->name('messages.unsave');
        Route::post('/messages/{message}/pin', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'pin'])->middleware('permission:communication.manage_channel')->name('messages.pin');
        Route::delete('/messages/{message}/pin', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'unpin'])->middleware('permission:communication.manage_channel')->name('messages.unpin');
        Route::put('/messages/{message}', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'updateMessage'])->middleware('permission:communication.send')->name('messages.update');
        Route::delete('/messages/{message}', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'deleteMessage'])->middleware('permission:communication.delete_own')->name('messages.destroy');
        Route::get('/announcements', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'announcements'])->middleware('permission:communication.view')->name('announcements.index');
        Route::post('/announcements', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'storeAnnouncement'])->middleware('permission:communication.announcements.create')->name('announcements.store');
        Route::post('/announcements/{announcement}/acknowledge', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'acknowledge'])->middleware('permission:communication.view')->name('announcements.acknowledge');
        Route::get('/notifications', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'notifications'])->middleware('permission:communication.view')->name('notifications.index');
        Route::post('/notifications', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'storeNotification'])->middleware('permission:communication.manage')->name('notifications.store');
        Route::get('/directory', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'directory'])->middleware('permission:communication.view')->name('directory.index');
        Route::get('/search', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'search'])->middleware('permission:communication.view')->name('search');
        Route::get('/settings', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'settings'])->middleware('permission:communication.view')->name('settings.show');
        Route::put('/settings', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'updateSettings'])->middleware('permission:communication.settings')->name('settings.update');
        Route::post('/context', [\Shared\Communication\Controllers\Api\CommunicationApiController::class, 'context'])->middleware('permission:communication.create_channel')->name('context.store');
    });
});
