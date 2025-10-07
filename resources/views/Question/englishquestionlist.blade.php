@extends('layouts.app')

@section('title', 'English Question List')

@section('content')

    {!! Toastr::message() !!}

    <!-- Page-content -->
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
    <div
        class="group-data-[sidebar-size=lg]:ltr:md:ml-vertical-menu group-data-[sidebar-size=lg]:rtl:md:mr-vertical-menu group-data-[sidebar-size=md]:ltr:ml-vertical-menu-md group-data-[sidebar-size=md]:rtl:mr-vertical-menu-md group-data-[sidebar-size=sm]:ltr:ml-vertical-menu-sm group-data-[sidebar-size=sm]:rtl:mr-vertical-menu-sm pt-[calc(theme('spacing.header')_*_1)] pb-[calc(theme('spacing.header')_*_0.8)] px-4 group-data-[navbar=bordered]:pt-[calc(theme('spacing.header')_*_1.3)] group-data-[navbar=hidden]:pt-0 group-data-[layout=horizontal]:mx-auto group-data-[layout=horizontal]:max-w-screen-2xl group-data-[layout=horizontal]:px-0 group-data-[layout=horizontal]:group-data-[sidebar-size=lg]:ltr:md:ml-auto group-data-[layout=horizontal]:group-data-[sidebar-size=lg]:rtl:md:mr-auto group-data-[layout=horizontal]:md:pt-[calc(theme('spacing.header')_*_1.6)] group-data-[layout=horizontal]:px-3 group-data-[layout=horizontal]:group-data-[navbar=hidden]:pt-[calc(theme('spacing.header')_*_0.9)]">
        <div class="container-fluid group-data-[content=boxed]:max-w-boxed mx-auto">

            <div class="flex flex-col gap-2 py-4 md:flex-row md:items-center print:hidden">
                <div class="grow">
                    <h5 class="text-16">English Question List</h5>
                </div>
                <ul class="flex items-center gap-2 text-sm font-normal shrink-0">
                    <li
                        class="relative before:content-['\ea54'] before:font-remix ltr:before:-right-1 rtl:before:-left-1  before:absolute before:text-[18px] before:-top-[3px] ltr:pr-4 rtl:pl-4 before:text-slate-400 dark:text-zink-200">
                        <a href="#!" class="text-slate-400 dark:text-zink-200">Master Entry</a>
                    </li>
                    <li class="text-slate-700 dark:text-zink-100">
                        English Question List
                    </li>
                </ul>
            </div>


            <div class="grid grid-cols-1 gap-x-5 xl:grid-cols-12">

                <div class="xl:col-span-12">
                    <div class="card" id="customerList">
                        <div class="card-body">
                            <div class="grid grid-cols-1 gap-5 mb-5 xl:grid-cols-0">


                            </div>

                            <div class="overflow-x-auto">



                                @if (!$englishques->isEmpty())
                                    <form id="bulkDeleteForm" method="POST" action="">
                                        @csrf
                                        <table class="w-full whitespace-nowrap" id="customerTable">
                                            <thead class="bg-slate-100 dark:bg-zink-600">
                                                <tr>

                                                    <th class="sort px-3.5 py-2.5 font-semibold border-b border-slate-200 dark:border-zink-500 "
                                                        data-sort="state_name">Sr.no </th>

                                                    <th class="sort px-3.5 py-2.5 font-semibold border-b border-slate-200 dark:border-zink-500 "
                                                        data-sort="state_name">Question</th>

                                                    <th class="sort px-3.5 py-2.5 font-semibold border-b border-slate-200 dark:border-zink-500 "
                                                        data-sort="state_name">option 1</th>

                                                    <th class="sort px-3.5 py-2.5 font-semibold border-b border-slate-200 dark:border-zink-500 "
                                                        data-sort="state_name">option 2</th>

                                                    <th class="sort px-3.5 py-2.5 font-semibold border-b border-slate-200 dark:border-zink-500 "
                                                        data-sort="state_name">option 3</th>

                                                    <th class="sort px-3.5 py-2.5 font-semibold border-b border-slate-200 dark:border-zink-500 "
                                                        data-sort="state_name">Correct Answer</th>

                                                    <th class="sort px-3.5 py-2.5 font-semibold border-b border-slate-200 dark:border-zink-500 "
                                                        data-sort="state_name">Image</th>


                                                </tr>
                                            </thead>
                                            <tbody class="list form-check-all">
                                                <?php $i = 1; ?>
                                                @foreach ($englishques as $englishque)
                                                    <tr class="text-center">


                                                        <td
                                                            class="px-3.5 py-2.5 border-y border-slate-200 dark:border-zink-500 id">
                                                            {{ $i++ }}
                                                        </td>


                                                        <td
                                                            class="px-3.5 py-2.5 border-y border-slate-200 dark:border-zink-500 job_title">
                                                            {{ $englishque->question }}
                                                        </td>
                                                        <td
                                                            class="px-3.5 py-2.5 border-y border-slate-200 dark:border-zink-500 job_title">
                                                            {{ $englishque->option_1 }}
                                                        </td>
                                                        <td
                                                            class="px-3.5 py-2.5 border-y border-slate-200 dark:border-zink-500 customer_name">

                                                            {{ $englishque->option_2 }}
                                                        </td>
                                                        <td
                                                            class="px-3.5 py-2.5 border-y border-slate-200 dark:border-zink-500 customer_name">

                                                            {{ $englishque->option_3 }}
                                                        </td>
                                                        <td
                                                            class="px-3.5 py-2.5 border-y border-slate-200 dark:border-zink-500 customer_name">

                                                            {{ $englishque->answer }}
                                                        </td>
                                                        <td
                                                            class="px-3.5 py-2.5 border-y border-slate-200 dark:border-zink-500 Categories_img">
                                                            @if ($englishque->image)
                                                                <img class="img-fluid"
                                                                    src="{{ asset('upload/questionImage') . '/' . $englishque->image }}"
                                                                    alt="" height="50" width="50">
                                                            @else
                                                                <img class="img-fluid"
                                                                    src="{{ asset('assets/images/noimage.png') }}"
                                                                    alt="" height="50" width="50">
                                                            @endif
                                                        </td>






                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <div class="flex items-center justify-between mt-5">
                                            {!! $englishques->links() !!}
                                        </div>
                                    </form>
                                @else
                                    <div class="noresult">
                                        <div class="text-center p-7">
                                            <h5 class="mb-2">Sorry! No Result Found</h5>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- End Page-content -->

        </div>
    </div>
@endsection
@section('script')
@endsection
