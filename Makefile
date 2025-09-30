build:
	echo "Building distribution zip..."
	cd ..
	zip -r wp-course-listings.zip wp-course-listings -x "wp-course-listings/.git/*" "wp-course-listings/README.md" "wp-course-listings/.gitignore"
	echo "Build complete: wp-course-listings.zip"
