#!/usr/bin/env python3
import glob
import os.path as path
import subprocess
import os, sys

pugdirs = [
	'application/views/exam/*.pug',
	'application/views/admin/*.pug'
]
exclude = {'common.pug'}
defext = '.html'
newext = '.php'
for pugdir in pugdirs:
	for pathn in glob.iglob(pugdir):
		filename = path.basename(pathn)
		if filename in exclude:
			continue
		ret = subprocess.call(['pug',pathn],shell=True)
		if ret == 0:
			try:
				os.unlink(path.splitext(pathn)[0] + newext)
			except FileNotFoundError:
				pass
			os.rename(path.splitext(pathn)[0] + defext, path.splitext(pathn)[0] + newext)
		else:
			sys.exit(1)