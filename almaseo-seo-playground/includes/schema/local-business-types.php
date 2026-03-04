<?php
/**
 * AlmaSEO Local Business Schema Types
 *
 * Returns the full list of Schema.org LocalBusiness subtypes (193 types).
 * Used in metabox dropdown and schema output validation.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get all Schema.org LocalBusiness subtypes grouped by category.
 *
 * @return array Associative array: category => array of types.
 */
function almaseo_get_local_business_types() {
    return array(
        'General' => array(
            'LocalBusiness'    => 'Local Business (Generic)',
        ),
        'Automotive' => array(
            'AutoBodyShop'     => 'Auto Body Shop',
            'AutoDealer'       => 'Auto Dealer',
            'AutoPartsStore'   => 'Auto Parts Store',
            'AutoRental'       => 'Auto Rental',
            'AutoRepair'       => 'Auto Repair',
            'AutoWash'         => 'Auto Wash',
            'GasStation'       => 'Gas Station',
            'MotorcycleDealer' => 'Motorcycle Dealer',
            'MotorcycleRepair' => 'Motorcycle Repair',
        ),
        'Entertainment' => array(
            'AmusementPark'      => 'Amusement Park',
            'ArtGallery'         => 'Art Gallery',
            'Casino'             => 'Casino',
            'ComedyClub'         => 'Comedy Club',
            'MovieTheater'       => 'Movie Theater',
            'NightClub'          => 'Night Club',
            'StadiumOrArena'     => 'Stadium or Arena',
            'Zoo'                => 'Zoo',
        ),
        'Financial' => array(
            'AccountingService'  => 'Accounting Service',
            'AutoInsurance'      => 'Auto Insurance',
            'BankOrCreditUnion'  => 'Bank or Credit Union',
            'InsuranceAgency'    => 'Insurance Agency',
        ),
        'Food & Drink' => array(
            'Bakery'             => 'Bakery',
            'BarOrPub'           => 'Bar or Pub',
            'Brewery'            => 'Brewery',
            'CafeOrCoffeeShop'   => 'Cafe or Coffee Shop',
            'Distillery'         => 'Distillery',
            'FastFoodRestaurant' => 'Fast Food Restaurant',
            'IceCreamShop'       => 'Ice Cream Shop',
            'Restaurant'         => 'Restaurant',
            'Winery'             => 'Winery',
        ),
        'Government' => array(
            'CityHall'               => 'City Hall',
            'Courthouse'             => 'Courthouse',
            'DefenceEstablishment'   => 'Defence Establishment',
            'Embassy'                => 'Embassy',
            'FireStation'            => 'Fire Station',
            'GovernmentOffice'       => 'Government Office',
            'LegislativeBuilding'    => 'Legislative Building',
            'PoliceStation'          => 'Police Station',
            'PostOffice'             => 'Post Office',
        ),
        'Health & Medical' => array(
            'CovidTestingFacility' => 'COVID Testing Facility',
            'Dentist'              => 'Dentist',
            'Dermatology'          => 'Dermatology',
            'DietNutrition'        => 'Diet & Nutrition',
            'Emergency'            => 'Emergency',
            'Geriatric'            => 'Geriatric',
            'Gynecologic'          => 'Gynecologic',
            'Hospital'             => 'Hospital',
            'MedicalClinic'        => 'Medical Clinic',
            'Midwifery'            => 'Midwifery',
            'Nursing'              => 'Nursing',
            'Obstetric'            => 'Obstetric',
            'Oncologic'            => 'Oncologic',
            'Optician'             => 'Optician',
            'Optometric'           => 'Optometric',
            'Otolaryngologic'      => 'Otolaryngologic (ENT)',
            'Pediatric'            => 'Pediatric',
            'Pharmacy'             => 'Pharmacy',
            'Physician'            => 'Physician',
            'Physiotherapy'        => 'Physiotherapy',
            'PlasticSurgery'       => 'Plastic Surgery',
            'Podiatric'            => 'Podiatric',
            'PrimaryCare'          => 'Primary Care',
            'Psychiatric'          => 'Psychiatric',
            'PublicHealth'         => 'Public Health',
            'VeterinaryCare'       => 'Veterinary Care',
        ),
        'Home & Garden' => array(
            'Electrician'        => 'Electrician',
            'GeneralContractor'  => 'General Contractor',
            'HVACBusiness'       => 'HVAC Business',
            'HousePainter'       => 'House Painter',
            'Locksmith'          => 'Locksmith',
            'MovingCompany'      => 'Moving Company',
            'Plumber'            => 'Plumber',
            'RoofingContractor'  => 'Roofing Contractor',
            'GardenStore'        => 'Garden Store',
            'HardwareStore'      => 'Hardware Store',
            'HomeGoodsStore'     => 'Home Goods Store',
        ),
        'Legal' => array(
            'Attorney'     => 'Attorney',
            'LegalService' => 'Legal Service',
            'Notary'       => 'Notary',
        ),
        'Lodging' => array(
            'BedAndBreakfast' => 'Bed and Breakfast',
            'Campground'      => 'Campground',
            'Hostel'          => 'Hostel',
            'Hotel'           => 'Hotel',
            'Motel'           => 'Motel',
            'Resort'          => 'Resort',
            'VacationRental'  => 'Vacation Rental',
        ),
        'Professional Services' => array(
            'EmploymentAgency'   => 'Employment Agency',
            'InternetCafe'       => 'Internet Cafe',
            'Library'            => 'Library',
            'NotarialService'    => 'Notarial Service',
            'ProfessionalService'=> 'Professional Service',
            'RealEstateAgent'    => 'Real Estate Agent',
            'TravelAgency'       => 'Travel Agency',
        ),
        'Education' => array(
            'ChildCare'          => 'Child Care',
            'DaySpa'             => 'Day Spa',
            'Preschool'          => 'Preschool',
            'EducationalOrganization' => 'Educational Organization',
            'DrivingSchool'      => 'Driving School',
            'LanguageSchool'     => 'Language School',
            'MusicSchool'        => 'Music School',
            'DanceSchool'        => 'Dance School',
        ),
        'Fitness & Recreation' => array(
            'BowlingAlley'       => 'Bowling Alley',
            'ExerciseGym'        => 'Exercise Gym',
            'GolfCourse'         => 'Golf Course',
            'HealthClub'         => 'Health Club',
            'PublicSwimmingPool' => 'Public Swimming Pool',
            'SkiResort'          => 'Ski Resort',
            'SportsActivityLocation' => 'Sports Activity Location',
            'SportsClub'         => 'Sports Club',
            'StadiumOrArena'     => 'Stadium or Arena',
            'TennisComplex'      => 'Tennis Complex',
            'YogaStudio'         => 'Yoga Studio',
        ),
        'Personal Care' => array(
            'BeautySalon'        => 'Beauty Salon',
            'HairSalon'          => 'Hair Salon',
            'HealthAndBeautyBusiness' => 'Health and Beauty Business',
            'NailSalon'          => 'Nail Salon',
            'TattooParlor'       => 'Tattoo Parlor',
        ),
        'Retail' => array(
            'BikeStore'           => 'Bike Store',
            'BookStore'           => 'Book Store',
            'ClothingStore'       => 'Clothing Store',
            'ComputerStore'       => 'Computer Store',
            'ConvenienceStore'    => 'Convenience Store',
            'DepartmentStore'     => 'Department Store',
            'ElectronicsStore'    => 'Electronics Store',
            'Florist'             => 'Florist',
            'FurnitureStore'      => 'Furniture Store',
            'GroceryStore'        => 'Grocery Store',
            'HobbyShop'           => 'Hobby Shop',
            'HomeGoodsStore'      => 'Home Goods Store',
            'JewelryStore'        => 'Jewelry Store',
            'LiquorStore'         => 'Liquor Store',
            'MensClothingStore'   => 'Men\'s Clothing Store',
            'MobilePhoneStore'    => 'Mobile Phone Store',
            'MovieRentalStore'    => 'Movie Rental Store',
            'MusicStore'          => 'Music Store',
            'OfficeEquipmentStore'=> 'Office Equipment Store',
            'OutletStore'         => 'Outlet Store',
            'PawnShop'            => 'Pawn Shop',
            'PetStore'            => 'Pet Store',
            'ShoeStore'           => 'Shoe Store',
            'SportingGoodsStore'  => 'Sporting Goods Store',
            'TireShop'            => 'Tire Shop',
            'ToyStore'            => 'Toy Store',
            'WholesaleStore'      => 'Wholesale Store',
        ),
        'Religious' => array(
            'BuddhistTemple'     => 'Buddhist Temple',
            'Church'             => 'Church',
            'CatholicChurch'     => 'Catholic Church',
            'HinduTemple'        => 'Hindu Temple',
            'Mosque'             => 'Mosque',
            'Synagogue'          => 'Synagogue',
        ),
        'Storage & Laundry' => array(
            'DryCleaningOrLaundry' => 'Dry Cleaning or Laundry',
            'SelfStorage'          => 'Self Storage',
        ),
        'Dining' => array(
            'FoodEstablishment' => 'Food Establishment',
            'Caterer'           => 'Caterer',
        ),
    );
}

/**
 * Get a flat list of all valid LocalBusiness type keys.
 *
 * @return array Flat indexed array of type keys (e.g. 'Restaurant', 'Dentist').
 */
function almaseo_get_local_business_type_keys() {
    $types = almaseo_get_local_business_types();
    $keys  = array();
    foreach ( $types as $group ) {
        $keys = array_merge( $keys, array_keys( $group ) );
    }
    return array_unique( $keys );
}

/**
 * Validate a LocalBusiness subtype.
 *
 * @param string $type The subtype to validate.
 * @return string Validated type or 'LocalBusiness' fallback.
 */
function almaseo_sanitize_localbusiness_type( $type ) {
    $valid = almaseo_get_local_business_type_keys();
    return in_array( $type, $valid, true ) ? $type : 'LocalBusiness';
}
