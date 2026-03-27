<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $transaction->razorpay_payment_id ?? $transaction->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .container { box-shadow: none; border: none; max-width: 100%; width: 100%; margin: 0; }
        }
        @page { size: A4; margin: 1cm; }
    </style>
</head>
<body class="bg-gray-50 py-12 px-4 font-sans leading-relaxed text-gray-800">

    <div class="container mx-auto max-w-2xl bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
        <!-- Receipt Header -->
        <div class="bg-slate-900 px-10 py-12 text-white relative">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
            
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <h1 class="text-3xl font-black tracking-tighter uppercase mb-1">GeoData</h1>
                    <p class="text-amber-500 font-bold text-xs tracking-[0.3em] uppercase">Premium API Services</p>
                </div>
                <div class="text-right">
                    <h2 class="text-xl font-bold opacity-60 uppercase tracking-widest text-sm mb-2">Receipt / Invoice</h2>
                    <p class="text-white font-mono text-sm">#{{ strtoupper(substr($transaction->razorpay_payment_id ?? 'FT'.$transaction->id, 0, 12)) }}</p>
                </div>
            </div>
        </div>

        <div class="p-10">
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-12 mb-12">
                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Billed To</h4>
                    <p class="font-bold text-gray-900 text-lg">{{ $user->name }}</p>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $user->company_name }}</p>
                </div>
                <div class="text-right">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Date of Issue</h4>
                    <p class="font-bold text-gray-900 italic">{{ $transaction->created_at->format('d M, Y') }}</p>
                    <p class="text-xs text-gray-400 mt-1 uppercase">{{ $transaction->created_at->format('h:i A') }}</p>
                </div>
            </div>

            <!-- Transaction Details Table -->
            <div class="mb-12">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b-2 border-slate-900/5">
                            <th class="py-4 font-bold text-gray-900 uppercase tracking-widest text-[10px]">Description</th>
                            <th class="py-4 text-right font-bold text-gray-900 uppercase tracking-widest text-[10px]">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="py-6">
                                <div class="font-bold text-gray-900">{{ $transaction->plan_name }} Subscription</div>
                                <div class="text-xs text-gray-500 mt-1">Unlimited Geo-Location API Access</div>
                            </td>
                            <td class="py-6 text-right font-bold text-gray-900">₹{{ number_format($transaction->amount, 2) }}</td>
                        </tr>
                        @if($transaction->discount_amount > 0)
                        <tr>
                            <td class="py-4 text-gray-500">
                                <span class="text-xs font-bold uppercase tracking-wide">Discount Applied</span>
                                @if($transaction->coupon_code)
                                    <span class="ml-2 text-[10px] bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full font-black uppercase">{{ $transaction->coupon_code }}</span>
                                @endif
                            </td>
                            <td class="py-4 text-right font-bold text-red-500">- ₹{{ number_format($transaction->discount_amount, 2) }}</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-900">
                            <td class="py-6 font-black text-gray-900 text-lg uppercase tracking-tight">Total Amount Paid</td>
                            <td class="py-6 text-right font-black text-gray-900 text-2xl tracking-tighter text-amber-600">
                                ₹{{ number_format($transaction->amount - $transaction->discount_amount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Bottom Note -->
            <div class="bg-gray-50 rounded-2xl p-6 mb-8 border border-gray-100 flex items-start gap-4">
                <i class="fas fa-shield-check text-green-500 mt-1 transform scale-125"></i>
                <p class="text-xs text-gray-500 leading-relaxed font-medium">
                    This is an electronically generated receipt. No signature is required. The amount mentioned above includes all applicable taxes. For any queries, please visit the dashboard or contact support.
                </p>
            </div>

            <!-- Navigation / Print Actions -->
            <div class="flex justify-between items-center no-print">
                <a href="{{ route('transactions.index') }}" class="text-gray-400 hover:text-gray-900 text-sm font-bold transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Transactions
                </a>
                <button onclick="window.print()" class="bg-slate-900 hover:bg-black text-white px-8 py-3 rounded-xl font-bold text-sm transition-all shadow-lg hover:shadow-slate-200">
                    <i class="fas fa-print mr-2"></i> Print Receipt
                </button>
            </div>
        </div>
        
        <div class="bg-gray-50 px-10 py-6 text-center">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-1">Thank you for choosing GeoData</p>
            <p class="text-xs text-gray-300">Transforming locations into insights.</p>
        </div>
    </div>

    <script>
        // Auto-print after load if specified
        window.addEventListener('load', () => {
             // window.print();
        });
    </script>
</body>
</html>
